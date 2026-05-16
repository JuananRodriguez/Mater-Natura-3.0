#!/usr/bin/env python3
"""
Re-intentar descarga de imágenes que fallaron por encoding.
"""
import urllib.request
import urllib.parse
import json
import os
import sys

UPLOADS_DIR = "/Users/juanan/projects/mater-natura/uploads"
DB_ARGS = ["mysql", "-u", "mn_user", "-pmn_secret_2024", "mater_natura"]
API_URL = "https://mater-natura.com/wp-json/wp/v2/posts"

def log(msg):
    print(f"  {msg}", flush=True)


# Obtener posts sin image_url
import subprocess
result = subprocess.run(
    DB_ARGS + ["-NBe", "SELECT id, slug FROM posts WHERE image_url IS NULL"],
    capture_output=True, text=True
)

posts_sin_imagen = []
for line in result.stdout.strip().split("\n"):
    if line:
        parts = line.split("\t")
        if len(parts) == 2:
            posts_sin_imagen.append((int(parts[0]), parts[1]))

log(f"Posts sin imagen: {len(posts_sin_imagen)}")

if not posts_sin_imagen:
    print("✅ Todas las imágenes están descargadas")
    sys.exit(0)

# Fetch ALL slugs from WP to map slug -> image URL
# We need to find the original WP post by our slug
# Since we modified slugs for duplicates, we need to query WP for ALL recent posts
import re

# Fetch a larger set to find matching posts
req = urllib.request.Request(
    f"{API_URL}?per_page=100&_fields=slug,class_list,yoast_head_json.og_image",
    headers={"User-Agent": "Mozilla/5.0"}
)
resp = urllib.request.urlopen(req, timeout=30)
all_wp_posts = json.loads(resp.read())

# Build a map of slug -> og_image URL
wp_slug_map = {}
for wp_post in all_wp_posts:
    og_images = wp_post.get("yoast_head_json", {}).get("og_image", [])
    if og_images and "url" in og_images[0]:
        wp_slug_map[wp_post["slug"]] = og_images[0]["url"]

log(f"Posts en WP: {len(wp_slug_map)} con imágenes")

downloaded = 0
for post_id, local_slug in posts_sin_imagen:
    # Try to find the original WP slug
    # Our local slugs may have suffixes like "-2", "-3" etc for dups
    # Strip numeric suffixes to find original
    base_slug = re.sub(r'-\d+$', '', local_slug)
    
    # Also try the exact local slug
    candidates = [local_slug, base_slug]
    
    found_url = None
    for c in candidates:
        if c in wp_slug_map:
            found_url = wp_slug_map[c]
            break
    
    # If not found by slug, also check all posts that start with base_slug
    if not found_url:
        for wp_slug, url in wp_slug_map.items():
            if wp_slug.startswith(base_slug):
                found_url = url
                break
    
    if not found_url:
        log(f"  ⚠ No se encontró imagen para slug '{local_slug}' (base: '{base_slug}')")
        continue
    
    # Download image - proper URL encoding for the path
    try:
        # Parse URL and re-encode the path component properly
        parsed = urllib.parse.urlparse(found_url)
        # Encode the path to handle special chars
        encoded_path = urllib.parse.quote(parsed.path, safe='/:')
        encoded_url = urllib.parse.urlunparse((
            parsed.scheme, parsed.netloc, encoded_path,
            parsed.params, parsed.query, parsed.fragment
        ))
        
        req2 = urllib.request.Request(encoded_url, headers={"User-Agent": "Mozilla/5.0"})
        resp2 = urllib.request.urlopen(req2, timeout=30)
        
        filename = os.path.basename(parsed.path)
        dest_path = os.path.join(UPLOADS_DIR, filename)
        os.makedirs(UPLOADS_DIR, exist_ok=True)
        
        with open(dest_path, "wb") as f:
            f.write(resp2.read())
        
        # Update DB
        subprocess.run(
            DB_ARGS + ["-e", 
                f"UPDATE posts SET image_url = '{filename}' WHERE id = {post_id}"],
            capture_output=True
        )
        
        log(f"  ✓ {filename} -> post #{post_id} ({local_slug})")
        downloaded += 1
        
    except Exception as e:
        log(f"  ✗ Error con '{found_url}': {e}")

print(f"\n✅ Descargadas {downloaded}/{len(posts_sin_imagen)} imágenes faltantes")
