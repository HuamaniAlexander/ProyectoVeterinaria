// Utilidad para subir archivos
class FileUploader {
    constructor(apiUrl = '/ProyectoVeterinaria/api/upload.php') {
        this.apiUrl = apiUrl;
    }
    
    async upload(file, tipo = 'general') {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('tipo', tipo);
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message);
            }
            
            return result.data;
        } catch (error) {
            console.error('Error al subir archivo:', error);
            throw error;
        }
    }
    
    // Preview de imagen antes de subir
    previewImage(file, previewElement) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewElement.src = e.target.result;
            previewElement.style.display = 'block';
        };
        
        reader.readAsDataURL(file);
    }
}

// Instancia global
const uploader = new FileUploader();

// Ejemplo de uso en formularios
document.addEventListener('DOMContentLoaded', function() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Mostrar preview si es imagen
            const preview = document.querySelector('.image-preview');
            if (preview && file.type.startsWith('image/')) {
                uploader.previewImage(file, preview);
            }
            
            // Subir automáticamente (opcional)
            // const tipo = this.dataset.tipo || 'general';
            // const result = await uploader.upload(file, tipo);
            // console.log('Archivo subido:', result);
        });
    });
});