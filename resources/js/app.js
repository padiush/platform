import './bootstrap';
import 'animate.css';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

function closeAlert() {
    document.getElementById('alert').classList.add('animate__animated', 'animate__fadeOutRight');
    setTimeout(function() {
        document.getElementById('alert').remove();
    }, 1000);
}

window.closeAlert = closeAlert;

function confirmAction(message) {
    return confirm(message);
}

window.confirmAction = confirmAction;

function startFormDesigner(csrfToken) {
    const csrfElement = document.createElement('input');
    csrfElement.setAttribute('type', 'hidden');
    csrfElement.setAttribute('name', '_token');
    csrfElement.setAttribute('value', csrfToken);

    var form = document.getElementById('form-designer');
}

function pushTextInput() {
    var form = document.getElementById('form-designer');

    var card = document.createElement('div');
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start');

    var cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    var cardTitle = document.createElement('h2');
    cardTitle.classList.add('card-title');
    cardTitle.innerHTML = 'Campo de texto';

    var nameFormControl = document.createElement('div');
    nameFormControl.classList.add('form-control', 'w-full');

    var nameLabel = document.createElement('label');
    nameLabel.classList.add('label');
    nameLabel.innerHTML = '<span class="label-text">Etiqueta del campo <span class="text-red-500">*</span></span>';

    var nameInput = document.createElement('input');
    nameInput.classList.add('input', 'input-bordered', 'w-full');
    nameInput.setAttribute('type', 'text');
    nameInput.setAttribute('placeholder', 'Ingrese el texto de la etiqueta');

    var innerGrid = document.createElement('div');
    innerGrid.classList.add('grid', 'grid-cols-1', 'lg:grid-cols-4', 'gap-4');

    var isMandatoryFormControl = document.createElement('div');
    isMandatoryFormControl.classList.add('form-control', 'w-full');

    var isMandatoryLabel = document.createElement('label');
    isMandatoryLabel.classList.add('label', 'cursor-pointer');
    isMandatoryLabel.innerHTML = '<span class="label-text">¿Es obligatorio?</span>';

    var isMandatoryCheckbox = document.createElement('input');
    isMandatoryCheckbox.setAttribute('type', 'checkbox');
    isMandatoryCheckbox.classList.add('checkbox', 'checkbox-primary');

    form.appendChild(card);
    card.appendChild(cardBody);
    cardBody.appendChild(cardTitle);

    cardBody.appendChild(nameFormControl);
    nameFormControl.appendChild(nameLabel);
    nameFormControl.appendChild(nameInput);

    cardBody.appendChild(innerGrid);

    innerGrid.appendChild(isMandatoryFormControl);
    isMandatoryFormControl.appendChild(isMandatoryLabel);
    isMandatoryLabel.appendChild(isMandatoryCheckbox);
}

function pushNumberInput(){
    var form = document.getElementById('form-designer');

    var card = document.createElement('div');
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start');

    var cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    var cardTitle = document.createElement('h2');
    cardTitle.classList.add('card-title');
    cardTitle.innerHTML = 'Campo numérico';

    var nameFormControl = document.createElement('div');
    nameFormControl.classList.add('form-control', 'w-full');

    var nameLabel = document.createElement('label');
    nameLabel.classList.add('label');
    nameLabel.innerHTML = '<span class="label-text">Etiqueta del campo <span class="text-red-500">*</span></span>';

    var nameInput = document.createElement('input');
    nameInput.classList.add('input', 'input-bordered', 'w-full');
    nameInput.setAttribute('type', 'text');
    nameInput.setAttribute('placeholder', 'Ingrese el texto de la etiqueta');

    var innerGrid = document.createElement('div');
    innerGrid.classList.add('grid', 'grid-cols-1', 'lg:grid-cols-4', 'gap-4');

    var isMandatoryFormControl = document.createElement('div');
    isMandatoryFormControl.classList.add('form-control', 'w-full');

    var isMandatoryLabel = document.createElement('label');
    isMandatoryLabel.classList.add('label', 'cursor-pointer');
    isMandatoryLabel.innerHTML = '<span class="label-text">¿Es obligatorio?</span>';

    var isMandatoryCheckbox = document.createElement('input');
    isMandatoryCheckbox.setAttribute('type', 'checkbox');
    isMandatoryCheckbox.classList.add('checkbox', 'checkbox-primary');

    var minValueFormControl = document.createElement('div');
    minValueFormControl.classList.add('form-control', 'w-full');

    var minValueLabel = document.createElement('label');
    minValueLabel.classList.add('label');
    minValueLabel.innerHTML = '<span class="label-text">Valor mínimo <span class="text-red-500">*</span></span>';

    var minValueInput = document.createElement('input');
    minValueInput.classList.add('input', 'input-bordered', 'w-full');
    minValueInput.setAttribute('type', 'number');
    minValueInput.setAttribute('placeholder', '0.00');

    var maxValueFormControl = document.createElement('div');
    maxValueFormControl.classList.add('form-control', 'w-full');

    var maxValueLabel = document.createElement('label');
    maxValueLabel.classList.add('label');
    maxValueLabel.innerHTML = '<span class="label-text">Valor máximo</span>';

    var maxValueInput = document.createElement('input');
    maxValueInput.classList.add('input', 'input-bordered', 'w-full');
    maxValueInput.setAttribute('type', 'number');
    maxValueInput.setAttribute('placeholder', '100.00');

    var stepFormControl = document.createElement('div');
    stepFormControl.classList.add('form-control', 'w-full');

    var stepLabel = document.createElement('label');
    stepLabel.classList.add('label');
    stepLabel.innerHTML = '<span class="label-text">Incremento <span class="text-red-500">*</span></span>';

    var stepInput = document.createElement('input');
    stepInput.classList.add('input', 'input-bordered', 'w-full');
    stepInput.setAttribute('type', 'number');
    stepInput.setAttribute('placeholder', '0.01');

    form.appendChild(card);
    card.appendChild(cardBody);
    cardBody.appendChild(cardTitle);

    cardBody.appendChild(nameFormControl);
    nameFormControl.appendChild(nameLabel);
    nameFormControl.appendChild(nameInput);

    cardBody.appendChild(innerGrid);

    innerGrid.appendChild(minValueFormControl);
    minValueFormControl.appendChild(minValueLabel);
    minValueFormControl.appendChild(minValueInput);

    innerGrid.appendChild(maxValueFormControl);
    maxValueFormControl.appendChild(maxValueLabel);
    maxValueFormControl.appendChild(maxValueInput);

    innerGrid.appendChild(stepFormControl);
    stepFormControl.appendChild(stepLabel);
    stepFormControl.appendChild(stepInput);

    innerGrid.appendChild(isMandatoryFormControl);
    isMandatoryFormControl.appendChild(isMandatoryLabel);
    isMandatoryLabel.appendChild(isMandatoryCheckbox);
}

function pushDateInput(){
    var form = document.getElementById('form-designer');

    var card = document.createElement('div');
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start');

    var cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    var cardTitle = document.createElement('h2');
    cardTitle.classList.add('card-title');
    cardTitle.innerHTML = 'Campo de fecha';

    var nameFormControl = document.createElement('div');
    nameFormControl.classList.add('form-control', 'w-full');

    var nameLabel = document.createElement('label');
    nameLabel.classList.add('label');
    nameLabel.innerHTML = '<span class="label-text">Etiqueta del campo <span class="text-red-500">*</span></span>';

    var nameInput = document.createElement('input');
    nameInput.classList.add('input', 'input-bordered', 'w-full');
    nameInput.setAttribute('type', 'text');
    nameInput.setAttribute('placeholder', 'Ingrese el texto de la etiqueta');

    var innerGrid = document.createElement('div');
    innerGrid.classList.add('grid', 'grid-cols-1', 'lg:grid-cols-4', 'gap-4');

    var isMandatoryFormControl = document.createElement('div');
    isMandatoryFormControl.classList.add('form-control', 'w-full');

    var isMandatoryLabel = document.createElement('label');
    isMandatoryLabel.classList.add('label', 'cursor-pointer');
    isMandatoryLabel.innerHTML = '<span class="label-text">¿Es obligatorio?</span>';

    var isMandatoryCheckbox = document.createElement('input');
    isMandatoryCheckbox.setAttribute('type', 'checkbox');
    isMandatoryCheckbox.classList.add('checkbox', 'checkbox-primary');

    form.appendChild(card);
    card.appendChild(cardBody);
    cardBody.appendChild(cardTitle);

    cardBody.appendChild(nameFormControl);
    nameFormControl.appendChild(nameLabel);
    nameFormControl.appendChild(nameInput);

    cardBody.appendChild(innerGrid);

    innerGrid.appendChild(isMandatoryFormControl);
    isMandatoryFormControl.appendChild(isMandatoryLabel);
    isMandatoryLabel.appendChild(isMandatoryCheckbox);
}

function pushSelect(){
    var form = document.getElementById('form-designer');

    var card = document.createElement('div');
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start');

    var cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    var cardTitle = document.createElement('h2');
    cardTitle.classList.add('card-title');
    cardTitle.innerHTML = 'Campo de selección';

    var nameFormControl = document.createElement('div');
    nameFormControl.classList.add('form-control', 'w-full');

    var nameLabel = document.createElement('label');
    nameLabel.classList.add('label');
    nameLabel.innerHTML = '<span class="label-text">Etiqueta del campo <span class="text-red-500">*</span></span>';

    var nameInput = document.createElement('input');
    nameInput.classList.add('input', 'input-bordered', 'w-full');
    nameInput.setAttribute('type', 'text');
    nameInput.setAttribute('placeholder', 'Ingrese el texto de la etiqueta');

    var innerGrid = document.createElement('div');
    innerGrid.classList.add('grid', 'grid-cols-1', 'lg:grid-cols-4', 'gap-4');

    var isMandatoryFormControl = document.createElement('div');
    isMandatoryFormControl.classList.add('form-control', 'w-full');

    var isMandatoryLabel = document.createElement('label');
    isMandatoryLabel.classList.add('label', 'cursor-pointer');
    isMandatoryLabel.innerHTML = '<span class="label-text">¿Es obligatorio?</span>';

    var isMandatoryCheckbox = document.createElement('input');
    isMandatoryCheckbox.setAttribute('type', 'checkbox');
    isMandatoryCheckbox.classList.add('checkbox', 'checkbox-primary');

    var optionsFormControl = document.createElement('div');
    optionsFormControl.classList.add('form-control', 'w-full');

    var optionsLabel = document.createElement('label');
    optionsLabel.classList.add('label');
    optionsLabel.innerHTML = '<span class="label-text">Opciones <span class="text-red-500">*</span></span>';

    var optionsInput = document.createElement('textarea');
    optionsInput.classList.add('textarea', 'textarea-bordered', 'w-full');
    optionsInput.setAttribute('rows', '3');
    optionsInput.setAttribute('placeholder', 'Ingrese las opciones separadas por comas');

    form.appendChild(card);
    card.appendChild(cardBody);
    cardBody.appendChild(cardTitle);

    cardBody.appendChild(nameFormControl);
    nameFormControl.appendChild(nameLabel);
    nameFormControl.appendChild(nameInput);

    cardBody.appendChild(optionsFormControl);
    optionsFormControl.appendChild(optionsLabel);
    optionsFormControl.appendChild(optionsInput);

    cardBody.appendChild(innerGrid);

    innerGrid.appendChild(isMandatoryFormControl);
    isMandatoryFormControl.appendChild(isMandatoryLabel);
    isMandatoryLabel.appendChild(isMandatoryCheckbox);
}

function pushMultiSelect(){
    var form = document.getElementById('form-designer');

    var card = document.createElement('div');
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start');

    var cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    var cardTitle = document.createElement('h2');
    cardTitle.classList.add('card-title');
    cardTitle.innerHTML = 'Campo de selección múltiple';

    var nameFormControl = document.createElement('div');
    nameFormControl.classList.add('form-control', 'w-full');

    var nameLabel = document.createElement('label');
    nameLabel.classList.add('label');
    nameLabel.innerHTML = '<span class="label-text">Etiqueta del campo <span class="text-red-500">*</span></span>';

    var nameInput = document.createElement('input');
    nameInput.classList.add('input', 'input-bordered', 'w-full');
    nameInput.setAttribute('type', 'text');
    nameInput.setAttribute('placeholder', 'Ingrese el texto de la etiqueta');

    var innerGrid = document.createElement('div');
    innerGrid.classList.add('grid', 'grid-cols-1', 'lg:grid-cols-4', 'gap-4');

    var isMandatoryFormControl = document.createElement('div');
    isMandatoryFormControl.classList.add('form-control', 'w-full');

    var isMandatoryLabel = document.createElement('label');
    isMandatoryLabel.classList.add('label', 'cursor-pointer');
    isMandatoryLabel.innerHTML = '<span class="label-text">¿Es obligatorio?</span>';

    var isMandatoryCheckbox = document.createElement('input');
    isMandatoryCheckbox.setAttribute('type', 'checkbox');
    isMandatoryCheckbox.classList.add('checkbox', 'checkbox-primary');

    var optionsFormControl = document.createElement('div');
    optionsFormControl.classList.add('form-control', 'w-full');

    var optionsLabel = document.createElement('label');
    optionsLabel.classList.add('label');
    optionsLabel.innerHTML = '<span class="label-text">Opciones <span class="text-red-500">*</span></span>';

    var optionsInput = document.createElement('textarea');
    optionsInput.classList.add('textarea', 'textarea-bordered', 'w-full');
    optionsInput.setAttribute('rows', '3');
    optionsInput.setAttribute('placeholder', 'Ingrese las opciones separadas por comas');

    form.appendChild(card);
    card.appendChild(cardBody);
    cardBody.appendChild(cardTitle);

    cardBody.appendChild(nameFormControl);
    nameFormControl.appendChild(nameLabel);
    nameFormControl.appendChild(nameInput);

    cardBody.appendChild(optionsFormControl);
    optionsFormControl.appendChild(optionsLabel);
    optionsFormControl.appendChild(optionsInput);

    cardBody.appendChild(innerGrid);

    innerGrid.appendChild(isMandatoryFormControl);
    isMandatoryFormControl.appendChild(isMandatoryLabel);
    isMandatoryLabel.appendChild(isMandatoryCheckbox);
}

function pushSingleSection(){
    var form = document.getElementById('form-designer');

    var divider = document.createElement('div');
    divider.classList.add('divider', 'section-single');
    divider.innerHTML = 'Sección única';

    form.appendChild(divider);
}

function pushRepeatingSection(){
    var form = document.getElementById('form-designer');

    var divider = document.createElement('div');
    divider.classList.add('divider', 'section-repeating');
    divider.innerHTML = 'Sección repetitiva';

    form.appendChild(divider);
}

window.startFormDesigner = startFormDesigner;
window.pushTextInput = pushTextInput;
window.pushNumberInput = pushNumberInput;
window.pushDateInput = pushDateInput;
window.pushSelect = pushSelect;
window.pushMultiSelect = pushMultiSelect;
window.pushSingleSection = pushSingleSection;
window.pushRepeatingSection = pushRepeatingSection;