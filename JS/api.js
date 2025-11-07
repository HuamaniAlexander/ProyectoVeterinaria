// Configuración base para todas las peticiones AJAX
const API = {
    baseURL: '/ProyectoVeterinaria/api/',
    
    // Método GET
    async get(endpoint, params = {}) {
        try {
            const url = new URL(this.baseURL + endpoint, window.location.origin);
            Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message);
            }
            
            return data;
        } catch (error) {
            console.error('Error en GET:', error);
            throw error;
        }
    },
    
    // Método POST
    async post(endpoint, data) {
        try {
            const response = await fetch(this.baseURL + endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message);
            }
            
            return result;
        } catch (error) {
            console.error('Error en POST:', error);
            throw error;
        }
    },
    
    // Método PUT
    async put(endpoint, data) {
        try {
            const response = await fetch(this.baseURL + endpoint, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message);
            }
            
            return result;
        } catch (error) {
            console.error('Error en PUT:', error);
            throw error;
        }
    },
    
    // Método DELETE
    async delete(endpoint, data) {
        try {
            const response = await fetch(this.baseURL + endpoint, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message);
            }
            
            return result;
        } catch (error) {
            console.error('Error en DELETE:', error);
            throw error;
        }
    }
};