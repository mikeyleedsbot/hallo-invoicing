import './bootstrap';
import 'flowbite';

import Alpine from 'alpinejs';
import TomSelect from 'tom-select';
import interact from 'interactjs';

window.Alpine = Alpine;
window.interact = interact; // Make interact globally available

// Tom Select directive for Alpine.js
Alpine.directive('tom-select', (el, { expression }, { evaluate }) => {
    const config = expression ? evaluate(expression) : {};
    
    const defaultConfig = {
        plugins: ['clear_button'],
        allowEmptyOption: true,
        ...config
    };
    
    new TomSelect(el, defaultConfig);
});

Alpine.start();
