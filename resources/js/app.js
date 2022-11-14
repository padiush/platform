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

function startFormDesigner(csrfToken, sections, getItemsRoute, getItemRoute, updateItemRoute, getSectionRoute, updateSectionRoute) {
    var form = document.getElementById('form-designer');

    sections.forEach(function(section){
        pushSection('', getSectionRoute, updateSectionRoute, csrfToken, false, section.id, section.repeatable);

        var section_id = section.id;

        $.ajax({
            url: getItemsRoute,
            type: 'POST',
            data: {
                section_id: section_id,
                _token: csrfToken
            },
            success: function(response){
                var items = response;
                items.forEach(function(item){
                    if(item.type == 'text'){
                        pushTextInput('', getItemRoute, updateItemRoute, csrfToken, false, item.id, section.id);
                    }

                    if(item.type == 'number'){
                        pushNumberInput('', getItemRoute, updateItemRoute, csrfToken, false, item.id, section.id);
                    }

                    if(item.type == 'date'){
                        pushDateInput('', getItemRoute, updateItemRoute, csrfToken, false, item.id, section.id);
                    }

                    if(item.type == 'select'){
                        pushSingleSelect('', getItemRoute, updateItemRoute, csrfToken, false, item.id, section.id);
                    }

                    if(item.type == 'multi'){
                        pushMultiSelect('', getItemRoute, updateItemRoute, csrfToken, false, item.id, section.id);
                    }
                });
            },
            error: function(error){
                console.log(error);
            }
        });
    });
}

function pushSection(createRoute, getRoute, updateRoute, token, create = true, id = null, repeatable = false){
    var form = document.getElementById('form-designer');

    var divider = document.createElement('div');
    divider.classList.add('divider', 'section-single');
    if(repeatable){
        divider.innerHTML = 'Sección repetitiva';
    } else {
        divider.innerHTML = 'Sección única';
    }

    var sectionDetailsCard = document.createElement('div');
    sectionDetailsCard.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start', 'animate__animated','animate__zoomIn');

    var cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    var jsonField = document.createElement('input');
    jsonField.setAttribute('type', 'hidden');
    jsonField.setAttribute('name', 'json');

    var cardTitle = document.createElement('h2');
    cardTitle.classList.add('card-title');
    cardTitle.innerHTML = 'Detalles de la sección';

    var sectionNameFormControl = document.createElement('div');
    sectionNameFormControl.classList.add('form-control');

    var sectionNameLabel = document.createElement('label');
    sectionNameLabel.classList.add('label');
    sectionNameLabel.innerHTML = '<span class="label-text">Nombre de la sección <span class="text-red-500">*</span></span>';

    var sectionNameInput = document.createElement('input');
    sectionNameInput.classList.add('input', 'input-bordered');
    sectionNameInput.setAttribute('type', 'text');
    sectionNameInput.setAttribute('placeholder', 'Nombre de la sección');
    sectionNameInput.setAttribute('id', 'section-name');

    var container = document.createElement('div');

    sectionDetailsCard.appendChild(cardBody);
    cardBody.appendChild(jsonField);
    cardBody.appendChild(cardTitle);
    cardBody.appendChild(sectionNameFormControl);
    sectionNameFormControl.appendChild(sectionNameLabel);
    sectionNameFormControl.appendChild(sectionNameInput);

    if(create){
        $.ajax({
            url: createRoute,
            type: 'POST',
            data: {
                _token: token,
                repeatable: repeatable ? "1" : "0",
            },
            success: function(response){
                var section_id = response.id;
                id = section_id;
                divider.setAttribute('data-section-id', section_id);
                form.appendChild(divider);
                form.appendChild(sectionDetailsCard);
                container.setAttribute('id', 'section-' + section_id);
                container.classList.add('section', 'grid', 'grid-cols-1', 'gap-4', 'w-full');
                form.appendChild(container);
                jsonField.value = JSON.stringify(response);
            },
            error: function(error){
                console.log(error);
            }
        });
    } else {
        $.ajax({
            url: getRoute,
            type: 'POST',
            data: {
                _token: token,
                id: id,
            },
            success: function(response){
                form.appendChild(divider);
                form.appendChild(sectionDetailsCard);
                container.setAttribute('id', 'section-' + id);
                container.classList.add('section', 'grid', 'grid-cols-1', 'gap-4', 'w-full');
                form.appendChild(container);
                sectionNameInput.value = response.name;
                jsonField.value = JSON.stringify(response);
            },
            error: function(error){
                console.log(error);
            }
        });
    }

    sectionNameInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.name = sectionNameInput.value;
        jsonField.value = JSON.stringify(json);

        updateSection(updateRoute, token, id, jsonField.value);
    });
}

function updateItem(route, token, item_id, json){
    $.ajax({
        url: route,
        type: 'POST',
        data: {
            _token: token,
            id: item_id,
            json: json,
        },
        success: function(response){
            console.log(response);
        },
        error: function(error){
            console.log(error);
        }
    });
}

function updateSection(route, token, section_id, json){
    $.ajax({
        url: route,
        type: 'POST',
        data: {
            _token: token,
            id: section_id,
            json: json,
        },
        success: function(response){
            console.log(response);
        },
        error: function(error){
            console.log(error);
        }
    });
}

function pushTextInput(createRoute, getRoute, updateRoute, token, create = true, id = null, section_id = null){
    if(section_id == null){
        var form = document.getElementById('form-designer');
        var sections = document.getElementsByClassName('section');
        var lastSection = sections[sections.length - 1];

        var container = document.getElementById(lastSection.id);
    } else {
        var container = document.getElementById('section-' + section_id);
    }

    var jsonField = document.createElement('input');
    jsonField.setAttribute('type', 'hidden');
    jsonField.setAttribute('name', 'json');

    var card = document.createElement('div');
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start', 'animate__animated','animate__zoomIn');

    var cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    var cardTitle = document.createElement('h2');
    cardTitle.classList.add('card-title');
    cardTitle.innerHTML = 'Campo de texto';

    var labelFormControl = document.createElement('div');
    labelFormControl.classList.add('form-control', 'w-full');

    var labelLabel = document.createElement('label');
    labelLabel.classList.add('label');
    labelLabel.innerHTML = '<span class="label-text">Etiqueta del campo <span class="text-red-500">*</span></span>';

    var labelInput = document.createElement('input');
    labelInput.classList.add('input', 'input-bordered', 'w-full');
    labelInput.setAttribute('type', 'text');
    labelInput.setAttribute('placeholder', 'Ingrese el texto de la etiqueta');

    var nameFormControl = document.createElement('div');
    nameFormControl.classList.add('form-control', 'w-full');

    var nameLabel = document.createElement('label');
    nameLabel.classList.add('label');
    nameLabel.innerHTML = '<span class="label-text">Nombre del campo <span class="text-red-500">*</span></span>';

    var nameInput = document.createElement('input');
    nameInput.classList.add('input', 'input-bordered', 'w-full');
    nameInput.setAttribute('type', 'text');
    nameInput.setAttribute('placeholder', 'Ingrese el nombre del campo');

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

    if(create){
        $.ajax({
            url: createRoute,
            type: 'POST',
            data: {
                _token: token,
                type: 'text',
            },
            success: function(response) {
                labelInput.setAttribute('label', 'label-' + response.id);
                nameInput.setAttribute('name', 'name-' + response.id);
                isMandatoryCheckbox.setAttribute('name', 'required-' + response.id);
                if(response.label != null){
                    labelInput.setAttribute('value', response.label);
                    nameInput.setAttribute('value', response.name);
                }
                if(response.required){
                    isMandatoryCheckbox.setAttribute('checked', 'checked');
                } else {
                    isMandatoryCheckbox.removeAttribute('checked');
                }
                jsonField.setAttribute('id', 'json-' + response.id);
                jsonField.setAttribute('value', JSON.stringify(response));
            },
            error: function(error) {
                console.log(error);
            }
        });
    } else {
        $.ajax({
            url: getRoute,
            type: 'POST',
            data: {
                _token: token,
                id: id,
            },
            success: function(response) {
                labelInput.setAttribute('label', 'label-' + response.id);
                nameInput.setAttribute('name', 'name-' + response.id);
                isMandatoryCheckbox.setAttribute('name', 'required-' + response.id);
                if(response.label != null){
                    labelInput.setAttribute('value', response.label);
                    nameInput.setAttribute('value', response.name);
                }
                if(response.required){
                    isMandatoryCheckbox.setAttribute('checked', 'checked');
                } else {
                    isMandatoryCheckbox.removeAttribute('checked');
                }
                jsonField.setAttribute('id', 'json-' + response.id);
                jsonField.setAttribute('value', JSON.stringify(response));
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    container.appendChild(card);
    card.appendChild(cardBody);
    cardBody.appendChild(cardTitle);
    cardBody.appendChild(jsonField);

    cardBody.appendChild(labelFormControl);
    labelFormControl.appendChild(labelLabel);
    labelFormControl.appendChild(labelInput);

    cardBody.appendChild(nameFormControl);
    nameFormControl.appendChild(nameLabel);
    nameFormControl.appendChild(nameInput);

    cardBody.appendChild(innerGrid);

    innerGrid.appendChild(isMandatoryFormControl);
    isMandatoryFormControl.appendChild(isMandatoryLabel);
    isMandatoryLabel.appendChild(isMandatoryCheckbox);

    labelInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.label = labelInput.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    nameInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.name = nameInput.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    isMandatoryCheckbox.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        if(isMandatoryCheckbox.checked){
            json.required = true;
        } else {
            json.required = false;
        }
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });
}

function pushNumberInput(createRoute, getRoute, updateRoute, token, create = true, id = null, section_id = null){
    if(section_id == null){
        var form = document.getElementById('form-designer');
        var sections = document.getElementsByClassName('section');
        var lastSection = sections[sections.length - 1];

        var container = document.getElementById(lastSection.id);
    } else {
        var container = document.getElementById('section-' + section_id);
    }
    var jsonField = document.createElement('input');
    jsonField.setAttribute('type', 'hidden');
    jsonField.setAttribute('name', 'json');

    var card = document.createElement('div');
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start', 'animate__animated','animate__zoomIn');

    var cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    var cardTitle = document.createElement('h2');
    cardTitle.classList.add('card-title');
    cardTitle.innerHTML = 'Campo numérico';

    var labelFormControl = document.createElement('div');
    labelFormControl.classList.add('form-control', 'w-full');

    var labelLabel = document.createElement('label');
    labelLabel.classList.add('label');
    labelLabel.innerHTML = '<span class="label-text">Etiqueta del campo <span class="text-red-500">*</span></span>';

    var labelInput = document.createElement('input');
    labelInput.classList.add('input', 'input-bordered', 'w-full');
    labelInput.setAttribute('type', 'text');
    labelInput.setAttribute('placeholder', 'Ingrese el texto de la etiqueta');

    var nameFormControl = document.createElement('div');
    nameFormControl.classList.add('form-control', 'w-full');

    var nameLabel = document.createElement('label');
    nameLabel.classList.add('label');
    nameLabel.innerHTML = '<span class="label-text">Nombre del campo <span class="text-red-500">*</span></span>';

    var nameInput = document.createElement('input');
    nameInput.classList.add('input', 'input-bordered', 'w-full');
    nameInput.setAttribute('type', 'text');
    nameInput.setAttribute('placeholder', 'Ingrese el nombre del campo');

    var mandatoryGrid = document.createElement('div');
    mandatoryGrid.classList.add('grid', 'grid-cols-1', 'lg:grid-cols-4', 'gap-4');

    var isMandatoryFormControl = document.createElement('div');
    isMandatoryFormControl.classList.add('form-control', 'w-full');

    var isMandatoryLabel = document.createElement('label');
    isMandatoryLabel.classList.add('label', 'cursor-pointer');
    isMandatoryLabel.innerHTML = '<span class="label-text">¿Es obligatorio?</span>';

    var isMandatoryCheckbox = document.createElement('input');
    isMandatoryCheckbox.setAttribute('type', 'checkbox');
    isMandatoryCheckbox.classList.add('checkbox', 'checkbox-primary');

    var numericGrid = document.createElement('div');
    numericGrid.classList.add('grid', 'grid-cols-1', 'lg:grid-cols-3', 'gap-4');

    var minFormControl = document.createElement('div');
    minFormControl.classList.add('form-control', 'w-full');

    var minLabel = document.createElement('label');
    minLabel.classList.add('label');
    minLabel.innerHTML = '<span class="label-text">Valor mínimo</span>';

    var minInput = document.createElement('input');
    minInput.classList.add('input', 'input-bordered', 'w-full');
    minInput.setAttribute('type', 'number');
    minInput.setAttribute('placeholder', 'Ingrese el valor mínimo');

    var maxFormControl = document.createElement('div');
    maxFormControl.classList.add('form-control', 'w-full');

    var maxLabel = document.createElement('label');
    maxLabel.classList.add('label');
    maxLabel.innerHTML = '<span class="label-text">Valor máximo</span>';

    var maxInput = document.createElement('input');
    maxInput.classList.add('input', 'input-bordered', 'w-full');
    maxInput.setAttribute('type', 'number');
    maxInput.setAttribute('placeholder', 'Ingrese el valor máximo');

    var stepFormControl = document.createElement('div');
    stepFormControl.classList.add('form-control', 'w-full');

    var stepLabel = document.createElement('label');
    stepLabel.classList.add('label');
    stepLabel.innerHTML = '<span class="label-text">Incremento</span>';

    var stepInput = document.createElement('input');
    stepInput.classList.add('input', 'input-bordered', 'w-full');
    stepInput.setAttribute('type', 'number');
    stepInput.setAttribute('placeholder', 'Ingrese el incremento');

    if(create){
        $.ajax({
            url: createRoute,
            type: 'POST',
            data: {
                _token: token,
                type: 'number'
            },
            success: function(response){
                jsonField.value = JSON.stringify(response);
                labelInput.setAttribute('label', 'label-' + response.id);
                nameInput.setAttribute('name', 'name-' + response.id);
                minInput.setAttribute('min', 'min-' + response.id);
                maxInput.setAttribute('max', 'max-' + response.id);
                stepInput.setAttribute('step', 'step-' + response.id);
                isMandatoryCheckbox.setAttribute('id', 'required-' + response.id);

                if(response.label != 'null'){
                    labelInput.value = response.label;
                }

                if(response.name != 'null'){
                    nameInput.value = response.name;
                }

                if(response.required){
                    isMandatoryCheckbox.checked = true;
                }

                if(response.min != 'null'){
                    minInput.value = response.min;
                }

                if(response.max != 'null'){
                    maxInput.value = response.max;
                }

                if(response.step != 'null'){
                    stepInput.value = response.step;
                }
            }
        });
    } else {
        $.ajax({
            url: getRoute,
            type: 'POST',
            data: {
                _token: token,
                id: id,
            },
            success: function(response){
                jsonField.value = JSON.stringify(response);
                labelInput.setAttribute('label', 'label-' + response.id);
                nameInput.setAttribute('name', 'name-' + response.id);
                minInput.setAttribute('min', 'min-' + response.id);
                maxInput.setAttribute('max', 'max-' + response.id);
                stepInput.setAttribute('step', 'step-' + response.id);
                isMandatoryCheckbox.setAttribute('id', 'required-' + response.id);

                if(response.label != 'null'){
                    labelInput.value = response.label;
                }

                if(response.name != 'null'){
                    nameInput.value = response.name;
                }

                if(response.required){
                    isMandatoryCheckbox.checked = true;
                }

                if(response.min != 'null'){
                    minInput.value = response.min;
                }

                if(response.max != 'null'){
                    maxInput.value = response.max;
                }

                if(response.step != 'null'){
                    stepInput.value = response.step;
                }
            }
        });
    }

    labelFormControl.appendChild(labelLabel);
    labelFormControl.appendChild(labelInput);

    nameFormControl.appendChild(nameLabel);
    nameFormControl.appendChild(nameInput);

    isMandatoryFormControl.appendChild(isMandatoryLabel);
    isMandatoryFormControl.appendChild(isMandatoryCheckbox);

    minFormControl.appendChild(minLabel);
    minFormControl.appendChild(minInput);

    maxFormControl.appendChild(maxLabel);
    maxFormControl.appendChild(maxInput);

    stepFormControl.appendChild(stepLabel);
    stepFormControl.appendChild(stepInput);

    mandatoryGrid.appendChild(isMandatoryFormControl);

    numericGrid.appendChild(minFormControl);
    numericGrid.appendChild(maxFormControl);
    numericGrid.appendChild(stepFormControl);

    container.appendChild(card)
    card.appendChild(cardBody);
    cardBody.appendChild(cardTitle);
    cardBody.appendChild(labelFormControl);
    cardBody.appendChild(jsonField);
    cardBody.appendChild(nameFormControl);
    cardBody.appendChild(numericGrid);
    cardBody.appendChild(mandatoryGrid);

    labelInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.label = this.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    nameInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.name = this.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    isMandatoryCheckbox.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.required = this.checked;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    minInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.min = this.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    maxInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.max = this.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    stepInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.step = this.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });
}

function pushDateInput(createRoute, getRoute, updateRoute, token, create = true, id = null, section_id = null){
    if(section_id == null){
        var form = document.getElementById('form-designer');
        var sections = document.getElementsByClassName('section');
        var lastSection = sections[sections.length - 1];

        var container = document.getElementById(lastSection.id);
    } else {
        var container = document.getElementById('section-' + section_id);
    }

    var jsonField = document.createElement('input');
    jsonField.setAttribute('type', 'hidden');
    jsonField.setAttribute('name', 'json');

    var card = document.createElement('div');
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start', 'animate__animated','animate__zoomIn');

    var cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    var cardTitle = document.createElement('h2');
    cardTitle.classList.add('card-title');
    cardTitle.innerHTML = 'Campo de fecha';

    var labelFormControl = document.createElement('div');
    labelFormControl.classList.add('form-control', 'w-full');

    var labelLabel = document.createElement('label');
    labelLabel.classList.add('label');
    labelLabel.innerHTML = '<span class="label-text">Etiqueta del campo <span class="text-red-500">*</span></span>';

    var labelInput = document.createElement('input');
    labelInput.classList.add('input', 'input-bordered', 'w-full');
    labelInput.setAttribute('type', 'text');
    labelInput.setAttribute('placeholder', 'Ingrese el texto de la etiqueta');

    var nameFormControl = document.createElement('div');
    nameFormControl.classList.add('form-control', 'w-full');

    var nameLabel = document.createElement('label');
    nameLabel.classList.add('label');
    nameLabel.innerHTML = '<span class="label-text">Nombre del campo <span class="text-red-500">*</span></span>';

    var nameInput = document.createElement('input');
    nameInput.classList.add('input', 'input-bordered', 'w-full');
    nameInput.setAttribute('type', 'text');
    nameInput.setAttribute('placeholder', 'Ingrese el nombre del campo');

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

    if(create){
        $.ajax({
            url: createRoute,
            type: 'POST',
            data: {
                _token: token,
                type: 'date',
            },
            success: function(response) {
                labelInput.setAttribute('label', 'label-' + response.id);
                nameInput.setAttribute('name', 'name-' + response.id);
                isMandatoryCheckbox.setAttribute('name', 'required-' + response.id);
                if(response.label != null){
                    labelInput.setAttribute('value', response.label);
                    nameInput.setAttribute('value', response.name);
                }
                if(response.required){
                    isMandatoryCheckbox.setAttribute('checked', 'checked');
                } else {
                    isMandatoryCheckbox.removeAttribute('checked');
                }
                jsonField.setAttribute('id', 'json-' + response.id);
                jsonField.setAttribute('value', JSON.stringify(response));
            },
            error: function(error) {
                console.log(error);
            }
        });
    } else {
        $.ajax({
            url: getRoute,
            type: 'POST',
            data: {
                _token: token,
                id: id,
            },
            success: function(response) {
                labelInput.setAttribute('label', 'label-' + response.id);
                nameInput.setAttribute('name', 'name-' + response.id);
                isMandatoryCheckbox.setAttribute('name', 'required-' + response.id);
                if(response.label != null){
                    labelInput.setAttribute('value', response.label);
                    nameInput.setAttribute('value', response.name);
                }
                if(response.required){
                    isMandatoryCheckbox.setAttribute('checked', 'checked');
                } else {
                    isMandatoryCheckbox.removeAttribute('checked');
                }
                jsonField.setAttribute('id', 'json-' + response.id);
                jsonField.setAttribute('value', JSON.stringify(response));
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    container.appendChild(card);
    card.appendChild(cardBody);
    cardBody.appendChild(cardTitle);
    cardBody.appendChild(jsonField);

    cardBody.appendChild(labelFormControl);
    labelFormControl.appendChild(labelLabel);
    labelFormControl.appendChild(labelInput);

    cardBody.appendChild(nameFormControl);
    nameFormControl.appendChild(nameLabel);
    nameFormControl.appendChild(nameInput);

    cardBody.appendChild(innerGrid);

    innerGrid.appendChild(isMandatoryFormControl);
    isMandatoryFormControl.appendChild(isMandatoryLabel);
    isMandatoryLabel.appendChild(isMandatoryCheckbox);

    labelInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.label = labelInput.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    nameInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.name = nameInput.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    isMandatoryCheckbox.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        if(isMandatoryCheckbox.checked){
            json.required = true;
        } else {
            json.required = false;
        }
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });
}

function pushSingleSelect(createRoute, getRoute, updateRoute, token, create = true, id = null, section_id = null){
    if(section_id == null){
        var form = document.getElementById('form-designer');
        var sections = document.getElementsByClassName('section');
        var lastSection = sections[sections.length - 1];

        var container = document.getElementById(lastSection.id);
    } else {
        var container = document.getElementById('section-' + section_id);
    }

    var jsonField = document.createElement('input');
    jsonField.setAttribute('type', 'hidden');
    jsonField.setAttribute('name', 'json');

    var card = document.createElement('div');
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start', 'animate__animated','animate__zoomIn');

    var cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    var cardTitle = document.createElement('h2');
    cardTitle.classList.add('card-title');
    cardTitle.innerHTML = 'Selección única';

    var labelFormControl = document.createElement('div');
    labelFormControl.classList.add('form-control', 'w-full');

    var labelLabel = document.createElement('label');
    labelLabel.classList.add('label');
    labelLabel.innerHTML = '<span class="label-text">Etiqueta del campo <span class="text-red-500">*</span></span>';

    var labelInput = document.createElement('input');
    labelInput.classList.add('input', 'input-bordered', 'w-full');
    labelInput.setAttribute('type', 'text');
    labelInput.setAttribute('placeholder', 'Ingrese el texto de la etiqueta');

    var nameFormControl = document.createElement('div');
    nameFormControl.classList.add('form-control', 'w-full');

    var nameLabel = document.createElement('label');
    nameLabel.classList.add('label');
    nameLabel.innerHTML = '<span class="label-text">Nombre del campo <span class="text-red-500">*</span></span>';

    var nameInput = document.createElement('input');
    nameInput.classList.add('input', 'input-bordered', 'w-full');
    nameInput.setAttribute('type', 'text');
    nameInput.setAttribute('placeholder', 'Ingrese el nombre del campo');

    var optionsFormControl = document.createElement('div');
    optionsFormControl.classList.add('form-control', 'w-full');

    var optionsLabel = document.createElement('label');
    optionsLabel.classList.add('label');
    optionsLabel.innerHTML = '<span class="label-text">Opciones <span class="text-red-500">*</span></span>';

    var optionsTextArea = document.createElement('textarea');
    optionsTextArea.classList.add('textarea', 'textarea-bordered', 'w-full');
    optionsTextArea.setAttribute('rows', '3');
    optionsTextArea.setAttribute('placeholder', 'Ingrese las opciones separadas por coma');

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

    if(create){
        $.ajax({
            url: createRoute,
            type: 'POST',
            data: {
                _token: token,
                type: 'select',
            },
            success: function(response) {
                labelInput.setAttribute('label', 'label-' + response.id);
                nameInput.setAttribute('name', 'name-' + response.id);
                isMandatoryCheckbox.setAttribute('name', 'required-' + response.id);

                if(response.label != "null" && response.label != null){
                    labelInput.setAttribute('value', response.label);
                }

                if(response.name != "null" && response.name != null){
                    nameInput.setAttribute('value', response.name);
                }

                if(response.required){
                    isMandatoryCheckbox.setAttribute('checked', 'checked');
                } else {
                    isMandatoryCheckbox.removeAttribute('checked');
                }
                optionsTextArea.setAttribute('name', 'options-' + response.id);
                if(response.options != "null" && response.options != null){
                    optionsTextArea.value = JSON.parse(response.options).join(',');
                }

                jsonField.setAttribute('id', 'json-' + response.id);
                jsonField.setAttribute('value', JSON.stringify(response));
            },
            error: function(error) {
                console.log(error);
            }
        });
    } else {
        $.ajax({
            url: getRoute,
            type: 'POST',
            data: {
                _token: token,
                id: id,
            },
            success: function(response) {
                labelInput.setAttribute('label', 'label-' + response.id);
                nameInput.setAttribute('name', 'name-' + response.id);
                isMandatoryCheckbox.setAttribute('name', 'required-' + response.id);
                if(response.label != "null" && response.label != null){
                    labelInput.setAttribute('value', response.label);
                }

                if(response.name != "null" && response.name != null){
                    nameInput.setAttribute('value', response.name);
                }

                if(response.required){
                    isMandatoryCheckbox.setAttribute('checked', 'checked');
                } else {
                    isMandatoryCheckbox.removeAttribute('checked');
                }
                optionsTextArea.setAttribute('name', 'options-' + response.id);
                if(response.options != "null" && response.options != null){
                    optionsTextArea.value = JSON.parse(response.options).join(',');
                }

                jsonField.setAttribute('id', 'json-' + response.id);
                jsonField.setAttribute('value', JSON.stringify(response));
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    container.appendChild(card);
    card.appendChild(cardBody);
    cardBody.appendChild(cardTitle);
    cardBody.appendChild(jsonField);

    cardBody.appendChild(labelFormControl);
    labelFormControl.appendChild(labelLabel);
    labelFormControl.appendChild(labelInput);

    cardBody.appendChild(nameFormControl);
    nameFormControl.appendChild(nameLabel);
    nameFormControl.appendChild(nameInput);

    cardBody.appendChild(optionsFormControl);
    optionsFormControl.appendChild(optionsLabel);
    optionsFormControl.appendChild(optionsTextArea);

    cardBody.appendChild(innerGrid);

    innerGrid.appendChild(isMandatoryFormControl);
    isMandatoryFormControl.appendChild(isMandatoryLabel);
    isMandatoryLabel.appendChild(isMandatoryCheckbox);

    labelInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.label = labelInput.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    nameInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.name = nameInput.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    isMandatoryCheckbox.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        if(isMandatoryCheckbox.checked){
            json.required = true;
        } else {
            json.required = false;
        }
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    optionsTextArea.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.options = optionsTextArea.value.split(',');
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });
}

function pushMultiSelect(createRoute, getRoute, updateRoute, token, create = true, id = null, section_id = null){
    if(section_id == null){
        var form = document.getElementById('form-designer');
        var sections = document.getElementsByClassName('section');
        var lastSection = sections[sections.length - 1];

        var container = document.getElementById(lastSection.id);
    } else {
        var container = document.getElementById('section-' + section_id);
    }

    var jsonField = document.createElement('input');
    jsonField.setAttribute('type', 'hidden');
    jsonField.setAttribute('name', 'json');

    var card = document.createElement('div');
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start', 'animate__animated','animate__zoomIn');

    var cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    var cardTitle = document.createElement('h2');
    cardTitle.classList.add('card-title');
    cardTitle.innerHTML = 'Selección múltiple';

    var labelFormControl = document.createElement('div');
    labelFormControl.classList.add('form-control', 'w-full');

    var labelLabel = document.createElement('label');
    labelLabel.classList.add('label');
    labelLabel.innerHTML = '<span class="label-text">Etiqueta del campo <span class="text-red-500">*</span></span>';

    var labelInput = document.createElement('input');
    labelInput.classList.add('input', 'input-bordered', 'w-full');
    labelInput.setAttribute('type', 'text');
    labelInput.setAttribute('placeholder', 'Ingrese el texto de la etiqueta');

    var nameFormControl = document.createElement('div');
    nameFormControl.classList.add('form-control', 'w-full');

    var nameLabel = document.createElement('label');
    nameLabel.classList.add('label');
    nameLabel.innerHTML = '<span class="label-text">Nombre del campo <span class="text-red-500">*</span></span>';

    var nameInput = document.createElement('input');
    nameInput.classList.add('input', 'input-bordered', 'w-full');
    nameInput.setAttribute('type', 'text');
    nameInput.setAttribute('placeholder', 'Ingrese el nombre del campo');

    var optionsFormControl = document.createElement('div');
    optionsFormControl.classList.add('form-control', 'w-full');

    var optionsLabel = document.createElement('label');
    optionsLabel.classList.add('label');
    optionsLabel.innerHTML = '<span class="label-text">Opciones <span class="text-red-500">*</span></span>';

    var optionsTextArea = document.createElement('textarea');
    optionsTextArea.classList.add('textarea', 'textarea-bordered', 'w-full');
    optionsTextArea.setAttribute('rows', '3');
    optionsTextArea.setAttribute('placeholder', 'Ingrese las opciones separadas por coma');

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

    if(create){
        $.ajax({
            url: createRoute,
            type: 'POST',
            data: {
                _token: token,
                type: 'multi',
            },
            success: function(response) {
                labelInput.setAttribute('label', 'label-' + response.id);
                nameInput.setAttribute('name', 'name-' + response.id);
                isMandatoryCheckbox.setAttribute('name', 'required-' + response.id);

                if(response.label != "null"){
                    labelInput.setAttribute('value', response.label);
                }

                if(response.name != "null"){
                    nameInput.setAttribute('value', response.name);
                }

                if(response.required){
                    isMandatoryCheckbox.setAttribute('checked', 'checked');
                } else {
                    isMandatoryCheckbox.removeAttribute('checked');
                }
                optionsTextArea.setAttribute('name', 'options-' + response.id);
                if(response.options != "null" && response.options != null){
                    optionsTextArea.value = JSON.parse(response.options).join(',');
                }

                jsonField.setAttribute('id', 'json-' + response.id);
                jsonField.setAttribute('value', JSON.stringify(response));
            },
            error: function(error) {
                console.log(error);
            }
        });
    } else {
        $.ajax({
            url: getRoute,
            type: 'POST',
            data: {
                _token: token,
                id: id,
            },
            success: function(response) {
                labelInput.setAttribute('label', 'label-' + response.id);
                nameInput.setAttribute('name', 'name-' + response.id);
                isMandatoryCheckbox.setAttribute('name', 'required-' + response.id);
                if(response.label != "null"){
                    labelInput.setAttribute('value', response.label);
                }

                if(response.name != "null"){
                    nameInput.setAttribute('value', response.name);
                }

                if(response.required){
                    isMandatoryCheckbox.setAttribute('checked', 'checked');
                } else {
                    isMandatoryCheckbox.removeAttribute('checked');
                }
                optionsTextArea.setAttribute('name', 'options-' + response.id);
                if(response.options != "null" && response.options != null){
                    optionsTextArea.value = JSON.parse(response.options).join(',');
                }

                jsonField.setAttribute('id', 'json-' + response.id);
                jsonField.setAttribute('value', JSON.stringify(response));
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    container.appendChild(card);
    card.appendChild(cardBody);
    cardBody.appendChild(cardTitle);
    cardBody.appendChild(jsonField);

    cardBody.appendChild(labelFormControl);
    labelFormControl.appendChild(labelLabel);
    labelFormControl.appendChild(labelInput);

    cardBody.appendChild(nameFormControl);
    nameFormControl.appendChild(nameLabel);
    nameFormControl.appendChild(nameInput);

    cardBody.appendChild(optionsFormControl);
    optionsFormControl.appendChild(optionsLabel);
    optionsFormControl.appendChild(optionsTextArea);

    cardBody.appendChild(innerGrid);

    innerGrid.appendChild(isMandatoryFormControl);
    isMandatoryFormControl.appendChild(isMandatoryLabel);
    isMandatoryLabel.appendChild(isMandatoryCheckbox);

    labelInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.label = labelInput.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    nameInput.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.name = nameInput.value;
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    isMandatoryCheckbox.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        if(isMandatoryCheckbox.checked){
            json.required = true;
        } else {
            json.required = false;
        }
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });

    optionsTextArea.addEventListener('change', function(){
        var json = JSON.parse(jsonField.value);
        json.options = optionsTextArea.value.split(',');
        jsonField.value = JSON.stringify(json);

        updateItem(updateRoute, token, json.id, jsonField.value);
    });
}

window.startFormDesigner = startFormDesigner;

window.pushSection = pushSection;

window.pushTextInput = pushTextInput;
window.pushNumberInput = pushNumberInput;
window.pushDateInput = pushDateInput;
window.pushSingleSelect = pushSingleSelect;
window.pushMultiSelect = pushMultiSelect;

function createNewRepeatingSectionInstance(section_id, route, token){
    var masterSectionId = "section-" + section_id + "-master";
    var masterSection = document.getElementById(masterSectionId);

    var instancesContainerId = "section-" + section_id + "-instances";
    var instancesContainer = document.getElementById(instancesContainerId);

    var instance = masterSection.cloneNode(true);
    instance.id = "section-" + section_id + "-instance-" + (instancesContainer.childElementCount + 1);
    instance.classList.add('animate__animated', 'animate__zoomIn');

    var deleteButton = document.createElement('button');
    deleteButton.classList.add('btn', 'btn-error', 'btn-sm', 'float-right');
    deleteButton.innerHTML = "Eliminar";
    deleteButton.addEventListener('click', function(){
        instance.classList.add('animate__animated', 'animate__zoomOut');
        setTimeout(function(){
            instance.remove();
        }, 500);
    });

    instance.appendChild(deleteButton);

    // Check if instance contains field with answer-repeatable class
    var answerRepeatable = instance.getElementsByClassName('answer-repeatable');

    if(answerRepeatable.length > 0){
        Object.values(answerRepeatable).forEach(function(element){
            var item_id = element.parentElement.id;

            // Set its id to answer-repeatable-{item_id}-{instance_number}
            element.id = "answer-repeatable-" + item_id + "-" + (instancesContainer.childElementCount + 1);

            // Attach a change event listener to the answer-repeatable
            element.addEventListener('change', function(){
                var value = $(this).val();
                var id = $(this).attr('id');

                var item_id = id.replace('answer-repeatable-', '').replace(/-[^-]*$/, '');
                var repeatable_index = id.replace('answer-repeatable-' + item_id + '-', '');

                var answer = {
                    'item_id': item_id,
                    'repeatable_index': repeatable_index,
                    'answer': value
                };

                var answerJSON = JSON.stringify(answer);

                storeAnswer(route, answerJSON, token);
            });
        });
    }

    instancesContainer.appendChild(instance);
}

window.createNewRepeatingSectionInstance = createNewRepeatingSectionInstance;

function storeAnswer(route, json, token){
    var answer = JSON.parse(json);

    $.ajax({
        url: route,
        type: 'POST',
        data: {
            _token: token,
            item_id: answer.item_id,
            repeatable_index: answer.repeatable_index,
            answer: answer.answer,
        },
        success: function(response) {
            console.log(response);
        },
        error: function(error) {
            console.log(error);
        }
    });
}

window.storeAnswer = storeAnswer;

function setAnswerToSingleField(getAnswerRoute, token, item_id, element){
    $.ajax({
        url: getAnswerRoute,
        type: 'POST',
        data: {
            _token: token,
            item_id: item_id,
        },
        success: function(response) {
            if(response != null && response.success == true){
                element.value = response.answer.answer;
            }
        },
        error: function(error) {
            console.log(error);
        }
    });
}

window.setAnswerToSingleField = setAnswerToSingleField;

function setAnswerToSingleMultiField(getAnswerRoute, token, item_id, elements){
    $.ajax({
        url: getAnswerRoute,
        type: 'POST',
        data: {
            _token: token,
            item_id: item_id,
        },
        success: function(response) {
            if(response != null && response.success == true){
                var answer = response.answer.answer;
                answer = JSON.parse(answer);

                for(var i = 0; i < elements.length; i++){
                    var element = elements[i];
                    if(answer.includes(element.value)){
                        element.checked = true;
                    } else {
                        element.checked = false;
                    }
                }
            }
        },
        error: function(error) {
            console.log(error);
        }
    });
}

window.setAnswerToSingleMultiField = setAnswerToSingleMultiField;

function populateRepeatableSection(container, route, token, section_id, storeAnswerRoute){
    $.ajax({
        url: route,
        type: 'POST',
        data: {
            _token: token,
            section_id: section_id,
        },
        success: function(response) {
            if(response != null && response.success == true){
                container.innerHTML = response.html;

                $('.answer-repeatable').change(function() {
                    var value = $(this).val();
                    var id = $(this).attr('id');

                    var item_id = id.replace('answer-repeatable-', '').replace(/-[^-]*$/, '');
                    var repeatable_index = id.replace('answer-repeatable-' + item_id + '-', '');

                    var answer = {
                        'item_id': item_id,
                        'repeatable_index': repeatable_index,
                        'answer': value
                    };

                    var answerJSON = JSON.stringify(answer);

                    storeAnswer(storeAnswerRoute, answerJSON, token);
            });
            }
        },
        error: function(error) {
            console.log(error);
        }
    });
}

window.populateRepeatableSection = populateRepeatableSection;