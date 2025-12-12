/**
 * Icon Picker Component
 * Componente reutilizable para seleccionar iconos Material Symbols
 * Requiere: icons-library.js y Material Symbols CSS
 */

class IconPicker {
    constructor(modalId = 'iconModal') {
        this.modal = document.getElementById(modalId);
        this.iconGrid = document.getElementById('iconGrid');
        this.iconSearch = document.getElementById('iconSearch');
        this.noResults = document.getElementById('noResults');
        this.btnClose = document.getElementById('btnCloseModal');
        
        this.currentTargetInput = null;
        this.currentTargetPreview = null;
        this.allIcons = getAllIcons();
        
        this.init();
    }
    
    init() {
        if (!this.modal) {
            console.error('IconPicker: Modal element not found');
            return;
        }
        
        // Event listeners
        this.btnClose?.addEventListener('click', () => this.close());
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.close();
        });
        
        this.iconSearch?.addEventListener('input', (e) => {
            const filtered = searchIcons(e.target.value);
            this.renderIcons(filtered);
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modal.classList.contains('hidden')) {
                this.close();
            }
        });
    }
    
    /**
     * Abre el modal para seleccionar un icono
     * @param {HTMLInputElement} inputElement - Input donde se guardará el icono seleccionado
     * @param {HTMLElement} previewElement - Elemento donde se mostrará el preview del icono
     */
    open(inputElement, previewElement = null) {
        this.currentTargetInput = inputElement;
        this.currentTargetPreview = previewElement;
        
        this.modal.classList.remove('hidden');
        this.iconSearch.value = '';
        this.renderIcons(this.allIcons);
        this.iconSearch.focus();
        
        // Marcar icono actual como seleccionado
        setTimeout(() => {
            const currentIcon = inputElement.value;
            document.querySelectorAll('.icon-option').forEach(opt => {
                if (opt.dataset.icon === currentIcon) {
                    opt.classList.add('selected');
                    opt.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            });
        }, 100);
    }
    
    close() {
        this.modal.classList.add('hidden');
    }
    
    /**
     * Renderiza los iconos en el grid
     * @param {Array} iconsToRender - Array de iconos a mostrar
     */
    renderIcons(iconsToRender) {
        this.iconGrid.innerHTML = '';
        
        if (iconsToRender.length === 0) {
            this.iconGrid.classList.add('hidden');
            this.noResults?.classList.remove('hidden');
            return;
        }
        
        this.iconGrid.classList.remove('hidden');
        this.noResults?.classList.add('hidden');
        
        iconsToRender.forEach(icon => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'icon-option flex flex-col items-center justify-center p-2 rounded-lg border-2 border-gray-200 hover:border-blue-500 transition cursor-pointer';
            btn.dataset.icon = icon;
            btn.innerHTML = `<span class="material-symbols-outlined text-2xl text-gray-700">${icon}</span>`;
            btn.title = icon;
            
            btn.addEventListener('click', () => this.selectIcon(icon));
            this.iconGrid.appendChild(btn);
        });
    }
    
    /**
     * Selecciona un icono y cierra el modal
     * @param {string} icon - Nombre del icono seleccionado
     */
    selectIcon(icon) {
        if (this.currentTargetInput) {
            this.currentTargetInput.value = icon;
            
            // Disparar evento input para que otros listeners se enteren
            const event = new Event('input', { bubbles: true });
            this.currentTargetInput.dispatchEvent(event);
        }
        
        if (this.currentTargetPreview) {
            this.currentTargetPreview.textContent = icon;
        }
        
        document.querySelectorAll('.icon-option').forEach(opt => {
            if (opt.dataset.icon === icon) {
                opt.classList.add('selected');
            } else {
                opt.classList.remove('selected');
            }
        });
        
        setTimeout(() => this.close(), 150);
    }
    
    /**
     * Configura un input con su botón y preview para usar el selector de iconos
     * @param {HTMLInputElement} inputElement - Input del icono
     * @param {HTMLButtonElement} buttonElement - Botón que abre el selector
     * @param {HTMLElement} previewElement - Elemento de preview del icono
     */
    attachToInput(inputElement, buttonElement, previewElement = null) {
        if (!inputElement || !buttonElement) {
            console.error('IconPicker.attachToInput: Missing required elements');
            return;
        }
        
        buttonElement.addEventListener('click', () => {
            this.open(inputElement, previewElement);
        });
        
        if (previewElement) {
            inputElement.addEventListener('input', (e) => {
                previewElement.textContent = e.target.value || 'place';
            });
        }
    }
    
    /**
     * Configura múltiples inputs de una sola vez
     * @param {string} containerSelector - Selector del contenedor padre
     * @param {Object} selectors - Objeto con selectores {input, button, preview}
     */
    attachToMultiple(containerSelector, selectors = {
        input: '.icon-input',
        button: '.icon-picker-btn',
        preview: '.icon-preview'
    }) {
        const container = document.querySelector(containerSelector);
        if (!container) return;
        
        const inputs = container.querySelectorAll(selectors.input);
        inputs.forEach(input => {
            const parent = input.closest('.icon-input-group') || input.parentElement;
            const button = parent.querySelector(selectors.button);
            const preview = parent.querySelector(selectors.preview);
            
            if (button) {
                this.attachToInput(input, button, preview);
            }
        });
    }
}

// Crear instancia global si se necesita
let iconPicker;

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('iconModal')) {
        iconPicker = new IconPicker();
    }
});