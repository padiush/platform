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

function startFormDesigner(csrfToken, sections, getItemsRoute, getItemRoute, updateItemRoute) {
    const csrfElement = document.createElement('input');
    csrfElement.setAttribute('type', 'hidden');
    csrfElement.setAttribute('name', '_token');
    csrfElement.setAttribute('value', csrfToken);

    var form = document.getElementById('form-designer');

    sections.forEach(function(section){
        if(section.repeatable){
            pushRepeatingSection('', '', false, section.id);

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
        } else {
            pushSingleSection('', '', false, section.id);

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
        }
    });
}

function pushSingleSection(route, token, create = true, id = null){
    var form = document.getElementById('form-designer');

    var divider = document.createElement('div');
    divider.classList.add('divider', 'section-single');
    divider.innerHTML = 'Sección única';

    var container = document.createElement('div');

    form.appendChild(divider);

    if(create){
        $.ajax({
            url: route,
            type: 'POST',
            data: {
                _token: token,
                repeatable: '0',
            },
            success: function(response){
                var section_id = response.id;
                divider.setAttribute('data-section-id', section_id);
                form.appendChild(divider);
                container.setAttribute('id', 'section-' + section_id);
                container.classList.add('section', 'grid', 'grid-cols-1', 'gap-4', 'w-full');
                form.appendChild(container);
            },
            error: function(error){
                console.log(error);
            }
        });
    } else {
        form.appendChild(divider);
        container.setAttribute('id', 'section-' + id);
        container.classList.add('section', 'grid', 'grid-cols-1', 'gap-4', 'w-full');
        form.appendChild(container);
    }
}

function pushRepeatingSection(route, token, create = true, id = null){
    var form = document.getElementById('form-designer');

    var divider = document.createElement('div');
    divider.classList.add('divider', 'section-repeating');
    divider.innerHTML = 'Sección repetitiva';

    var container = document.createElement('div');

    if(create){
        $.ajax({
            url: route,
            type: 'POST',
            data: {
                _token: token,
                repeatable: '1',
            },
            success: function(response){
                var section_id = response.id;
                divider.setAttribute('data-section-id', section_id);
                form.appendChild(divider);
                container.setAttribute('id', 'section-' + section_id);
                container.classList.add('section', 'grid', 'grid-cols-1', 'gap-4', 'w-full');
                form.appendChild(container);
            },
            error: function(error){
                console.log(error);
            }
        });
    } else {
        form.appendChild(divider);
        container.setAttribute('id', 'section-' + id);
        container.classList.add('section', 'grid', 'grid-cols-1', 'gap-4', 'w-full');
        form.appendChild(container);
    }
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
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start');

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
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start');

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
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start');

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
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start');

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
    card.classList.add('card', 'w-full', 'bg-base-100', 'shadow-xl', 'text-base-content', 'overflow-x-auto', 'self-start');

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

window.pushSingleSection = pushSingleSection;
window.pushRepeatingSection = pushRepeatingSection;

window.pushTextInput = pushTextInput;
window.pushNumberInput = pushNumberInput;
window.pushDateInput = pushDateInput;
window.pushSingleSelect = pushSingleSelect;
window.pushMultiSelect = pushMultiSelect;