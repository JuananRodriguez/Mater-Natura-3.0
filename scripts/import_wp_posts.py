#!/usr/bin/env python3
"""
Importador de posts desde WordPress API de Mater-Natura.
Migra 50 posts (light y dark) con sus imágenes.
"""

import urllib.request
import json
import os
import subprocess
import sys
import re

API_URL = "https://mater-natura.com/wp-json/wp/v2/posts"
UPLOADS_DIR = "/Users/juanan/projects/mater-natura/uploads"
USER_ID = 4
DB_ARGS = ["mysql", "-u", "mn_user", "-pmn_secret_2024", "mater_natura"]

def log(msg):
    print(f"  {msg}", flush=True)


def fetch_json(url):
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    resp = urllib.request.urlopen(req, timeout=30)
    return json.loads(resp.read())


def download_image(url, dest_dir):
    """Descarga una imagen y devuelve el nombre del archivo, o None."""
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
        resp = urllib.request.urlopen(req, timeout=30)
        if resp.status != 200:
            return None
        
        # Extraer filename de la URL
        filename = os.path.basename(url.split("?")[0])
        if not filename or "." not in filename:
            filename = f"img_{hash(url) & 0xFFFFFFFF}.jpg"
        
        dest_path = os.path.join(dest_dir, filename)
        os.makedirs(dest_dir, exist_ok=True)
        
        with open(dest_path, "wb") as f:
            f.write(resp.read())
        
        return filename
    except Exception as e:
        log(f"  ⚠ Error descargando imagen: {e}")
        return None


def mysql_execute(sql, params=None):
    """Ejecuta SQL via CLI de mysql."""
    cmd = DB_ARGS + ["-e", sql]
    result = subprocess.run(cmd, capture_output=True, text=True)
    if result.returncode != 0:
        log(f"  ✗ Error SQL: {result.stderr.strip()}")
        return False
    return True


def sql_escape(val):
    """Escapa un valor para SQL (escapando comillas simples)."""
    if val is None:
        return "NULL"
    return "'" + str(val).replace("'", "''") + "'"


# ─── 1. Obtener slugs existentes ───
log("Obteniendo slugs existentes...")
result = subprocess.run(DB_ARGS + ["-NBe", "SELECT slug FROM posts"], capture_output=True, text=True)
existing_slugs = set(result.stdout.strip().split("\n")) if result.stdout.strip() else set()
log(f"  {len(existing_slugs)} slugs existentes en DB")

# ─── 2. Fetch 50 posts de la API ───
log(f"\nFetching 50 posts desde {API_URL}...")
try:
    posts = fetch_json(f"{API_URL}?per_page=50&_fields=id,title,slug,content,class_list,yoast_head_json.og_image,date")
except Exception as e:
    log(f"Error fetching posts: {e}")
    sys.exit(1)

log(f"  Recibidos {len(posts)} posts")

# ─── 3. Procesar cada post ───
imported = 0
light_count = 0
dark_count = 0

for i, post in enumerate(posts, 1):
    wp_id = post["id"]
    slug = post["slug"]
    title = post["title"]["rendered"]
    content = post["content"]["rendered"]
    date = post["date"]
    
    # Determinar template (light/dark)
    class_list = post.get("class_list", [])
    is_dark = any("category-oscuro" in c for c in class_list)
    template = "dark" if is_dark else "light"
    
    # Obtener imagen destacada
    image_url = None
    og_images = post.get("yoast_head_json", {}).get("og_image", [])
    if og_images and "url" in og_images[0]:
        image_url = og_images[0]["url"]
    
    # Evitar slugs duplicados
    original_slug = slug
    if slug in existing_slugs:
        suffix = 1
        while f"{slug}-{suffix}" in existing_slugs:
            suffix += 1
        slug = f"{slug}-{suffix}"
    
    # Descargar imagen
    local_image = None
    if image_url:
        local_image = download_image(image_url, UPLOADS_DIR)
    
    # Insertar en DB
    try:
        sql = (
            "INSERT INTO posts (user_id, title, slug, description, image_url, template, status, visibility, published_at) VALUES ("
            f"{USER_ID}, "
            f"{sql_escape(title)}, "
            f"{sql_escape(slug)}, "
            f"{sql_escape(content)}, "
            f"{sql_escape(local_image)}, "
            f"{sql_escape(template)}, "
            f"'published', 'public', "
            f"{sql_escape(date)})"
        )
        
        if not mysql_execute(sql):
            log(f"  ✗ [{i}/50] {title} — fallo al insertar")
            continue
        
        existing_slugs.add(slug)
        imported += 1
        
        if template == "light":
            light_count += 1
        else:
            dark_count += 1
        
        img_status = f" 🖼 {local_image}" if local_image else ""
        slug_note = f" (slug ajustado: {slug})" if slug != original_slug else ""
        log(f"  ✓ [{i}/50] [{template}] {title} -> /{slug}{slug_note}{img_status}")
        
    except Exception as e:
        log(f"  ✗ [{i}/50] {title} — error: {e}")

# ─── 4. Resumen ───
print(f"\n{'═' * 50}")
print(f"  ✅ Importación completada")
print(f"  📝 Total: {imported} posts ({light_count} light, {dark_count} dark)")
print(f"  📁 Imágenes guardadas en: {UPLOADS_DIR}")
print(f"{'═' * 50}")
