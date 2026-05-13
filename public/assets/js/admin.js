/**
 * Mater-Natura — Admin JavaScript
 *
 * Funcionalidades compartidas del panel de administracion.
 */

;(function () {
    'use strict';

    // ─── Quill Editor ───
    function initQuillEditor() {
        const textarea = document.getElementById('editor-description');
        if (!textarea) return;

        // Crear contenedor para Quill
        const editorId = 'quill-editor';
        const editorDiv = document.createElement('div');
        editorDiv.id = editorId;
        editorDiv.style.height = '350px';
        textarea.parentNode.insertBefore(editorDiv, textarea.nextSibling);

        // Ocultar textarea original
        textarea.style.display = 'none';

        // Cargar valor inicial desde el textarea
        let initialValue = textarea.value || '';

        // Inicializar Quill
        const quill = new Quill('#' + editorId, {
            theme: 'snow',
            placeholder: 'Escribe tu contenido aquí…',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['blockquote', 'code-block'],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });

        // Cargar el contenido inicial (HTML)
        if (initialValue) {
            quill.root.innerHTML = initialValue;
        }

        // ─── Image Upload Handler ───
        quill.getModule('toolbar').addHandler('image', function () {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/jpeg,image/png,image/webp');
            input.click();

            input.onchange = function () {
                const file = input.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('image', file);

                // Crear indicador de carga
                const range = quill.getSelection(true);
                quill.insertText(range.index, '\n⏳ Subiendo imagen…\n', 'user');

                fetch('/admin/upload-image', {
                    method: 'POST',
                    body: formData
                })
                .then(function (res) {
                    if (!res.ok) throw new Error('Error al subir la imagen');
                    return res.json();
                })
                .then(function (data) {
                    // Eliminar texto de carga
                    const currentText = quill.getText();
                    const loadingIdx = currentText.lastIndexOf('Subiendo imagen…');
                    if (loadingIdx >= 0) {
                        quill.deleteText(loadingIdx - 1, 'Subiendo imagen…'.length + 2);
                    }

                    // Insertar imagen
                    const idx = quill.getSelection(true).index;
                    quill.insertEmbed(idx, 'image', data.url);
                    quill.setSelection(idx + 1);
                })
                .catch(function (err) {
                    alert('Error al subir la imagen: ' + err.message);
                    const currentText = quill.getText();
                    const loadingIdx = currentText.lastIndexOf('Subiendo imagen…');
                    if (loadingIdx >= 0) {
                        quill.deleteText(loadingIdx - 1, 'Subiendo imagen…'.length + 2);
                    }
                });
            };
        });

        // ─── Sincronizar con textarea antes de enviar el formulario ───
        const form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                textarea.value = quill.root.innerHTML;
            });
        }
    }

    // ─── Inicializar en DOM listo ───
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initQuillEditor);
    } else {
        initQuillEditor();
    }
})();
