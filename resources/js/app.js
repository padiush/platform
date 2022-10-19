import './bootstrap';
import 'animate.css';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

function closeAlert() {
    document.getElementById('alert').classList.add('animate__animated', 'animate__fadeOutRight');
    setTimeout(function() {
        // Remove the element from the DOM
        document.getElementById('alert').remove();
    }, 1000);
}

window.closeAlert = closeAlert;