function createBoxCard(box, box_array, items, element, index = 0) {
    const container_box = document.createElement('div');
    const box_header = document.createElement('div');
    const box_body = document.createElement('div');
    const box_footer = document.createElement('div');
    const group = document.createElement('div');
    const select_items = document.createElement('select');
    const item_button_plus = document.createElement('button');
    const remove_box = document.createElement('button');
    const content_items = document.createElement('div');

    document.getElementById('sycro_with_shipsmart').removeAttribute('disabled');
    container_box.classList.add(`ShipSmart__box-card`);
    container_box.setAttribute('data-box-id', index);
    box_header.classList.add('ShipSmart__box-header');
    box_body.classList.add('ShipSmart__box-body');
    box_footer.classList.add('ShipSmart__box-footer');
    group.classList.add('ShipSmart__box-group');
    select_items.classList.add(`ShipSmart__box-select--${index}`);
    content_items.classList.add(`ShipSmart__box-content--${index}`);
    content_items.classList.add(`ShipSmart__box-items`);
    item_button_plus.classList.add(`ShipSmart__box-button`);
    item_button_plus.setAttribute('type', 'button');
    item_button_plus.innerText = '+';
    box_header.innerHTML = `<h2 class="ShipSmart__box-title"><span>Caixa ${index + 1}</span></h2>`;
    remove_box.classList.add('ShipSmart__box-remove');
    remove_box.classList.add(`add_note`);
    remove_box.classList.add(`button`);
    remove_box.setAttribute('type', 'button');
    remove_box.innerText = 'X';
    remove_box.onclick = () => removeBoxElement(index, box_array, container_box, items, box);
    box_header.append(remove_box);
    item_button_plus.onclick = () => addItemInBox(index, box_array, items, box);

    renderSelect(select_items, items);

    group.append(select_items);
    group.append(item_button_plus);

    box_body.append(group);
    box_body.append(content_items);

    box_footer.innerHTML = `<span class="ShipSmart__box-price--${index}">Preço total: R$ 0.00</span>`;

    container_box.append(box_header);
    container_box.append(box_body);
    container_box.append(box_footer);
    element.append(container_box);
}

function addItemInBox(box_index, box_array = [], items = [], box) {
    const content_items = document.querySelector(`.ShipSmart__box-content--${box_index}`);
    const boxes_cards = document.querySelectorAll('.ShipSmart__box-card');
    const box_select = document.querySelector(`.ShipSmart__box-select--${box_index}`);
    const priceElement = document.querySelector(`.ShipSmart__box-price--${box_index}`);
    const itemId = box_array.filter(box => {
        return (parseInt(box.id) - 1) == box_index;
    })[0].items.length;

    const itemElement = document.createElement('div');
    let priceTotal = 0;
    let weightTotal = 0;
    let itemsValidation = [];

    const item = items.filter((item) => {
        return item.id === parseInt(box_select.value);
    });

    box_array.map(box => {
        if ((parseInt(box.id) - 1) == box_index) {
            itemsValidation = box.items.concat(item[0]);
        }
    });

    const box_validate = {
        items: itemsValidation
    };

    const box_dimensions_validate = {
        weight: getWeightBox(box_validate),
        height: getHeightBox(box_validate),
        width: getWidthBox(box_validate),
        lengthBox: getLengthBox(box_validate),
    } 

    if (exceedLimitBox(box_dimensions_validate, box)) {
        alert(`Atenção! Essa caixa não comporta este produto, tente criar outra caixa.`);
        return;
    }

    box_array.map(box => {
        if ((parseInt(box.id) - 1) == box_index) {
            box.items.push(item[0]);

            weightTotal = getWeightBox(box);
            priceTotal = getPriceBox(box);

            box.price = priceTotal;
            box.weight = weightTotal;
            renderPrice(priceElement, priceTotal);
        }
    });

    itemElement.innerHTML = `<span class="ShipSmart__box-item">${item[0].name} </span><button class="add_note button ShipSmart__box-item--removed">x</button>`;
    itemElement.querySelector('.ShipSmart__box-item--removed').onclick= () => {
        itemElement.remove();
        removeItemInBox(box_index, box_array, itemId, items, box);
    };
    content_items.append(itemElement);

    items.splice(box_select.options.selectedIndex, 1);

    Array.from(boxes_cards).map((group, index) => {
        const box_id = group.getAttribute('data-box-id'); 
        const box_select = group.querySelector('select');
        const item_button_plus = group.querySelector(`.ShipSmart__box-button`);
        const remove_box = group.querySelector(`.ShipSmart__box-remove`);
        box_select.innerHTML = '';
        renderSelect(box_select, items);
        item_button_plus.onclick = () => addItemInBox(box_id, box_array, items, box);
        remove_box.onclick = () => removeBoxElement(box_id, box_array, group, items, box);
    });
}

function getPriceBox(box_array) {
    return box_array.items.reduce((carry, item) => {
        carry += parseFloat(item.details.price);

        return carry;
    }, 0).toFixed(2)
}

function getWeightBox(box_array) {
    return box_array.items.reduce((carry, item) => {
        carry += parseFloat(item.details.weight);

        return carry;
    }, 0).toFixed(2)
}

function getHeightBox(box_array) {
    return box_array.items.reduce((carry, item) => {
        carry += parseFloat(item.details.height);

        return carry;
    }, 0).toFixed(2)
}

function getWidthBox(box_array) {
    return box_array.items.reduce((carry, item) => {
        carry += parseFloat(item.details.width);

        return carry;
    }, 0).toFixed(2)
}

function getLengthBox(box_array) {
    return box_array.items.reduce((carry, item) => {
        carry += parseFloat(item.details.length);

        return carry;
    }, 0).toFixed(2)
}

function renderSelect(select, items) {
    Array.from(items).map(item => {
        const item_option = new Option(item['name'], item['id']);
        select.append(item_option);
    });
}

function renderPrice(element, price) {
    element.innerHTML = `Preço total: R$${price}`;
}

function removeItemInBox(box_index, box_array = [], itemId, items = [], box) {
    const content_items = document.querySelector(`.ShipSmart__box-content--${box_index}`);
    const boxes_cards = document.querySelectorAll('.ShipSmart__box-card');
    const priceElement = document.querySelector(`.ShipSmart__box-price--${box_index}`);
    let priceTotal = 0;
    let weightTotal = 0;
    content_items.innerHTML = '';
    document.getElementById('sycro_with_shipsmart').removeAttribute('disabled'); 

    box_array.map(box => {
        if ((parseInt(box.id) - 1) == box_index) {
            items.push(box.items[itemId]);
        }
    });

    box_array.map(box => {
        if ((parseInt(box.id) - 1) == box_index) {
            box.items.splice(itemId, 1);
            box.items.map((item, index) => {
                const itemElement = document.createElement('div');
                itemElement.innerHTML = `<span class="ShipSmart__box-item">${item.name} <span><i style="cursor:pointer;" class="ShipSmart__box-item--removed">x</i>`;
                itemElement.querySelector('.ShipSmart__box-item--removed').onclick= () => {
                itemElement.remove();
                    removeItemInBox(box_index, box_array, index, items);
                };
                content_items.append(itemElement);
            });

            priceTotal = getPriceBox(box);
            weightTotal = getWeightBox(box);

            box.price = priceTotal;
            box.weight = weightTotal;
            renderPrice(priceElement, priceTotal);
        }
    });


    Array.from(boxes_cards).map((group, index) => {
        const box_id = group.getAttribute('data-box-id'); 
        const box_select = group.querySelector('select');
        const item_button_plus = group.querySelector(`.ShipSmart__box-button`);
        const remove_box = group.querySelector(`.ShipSmart__box-remove`);
        box_select.innerHTML = '';
        renderSelect(box_select, items);
        item_button_plus.onclick = () => addItemInBox(box_id, box_array, items, box);
        remove_box.onclick = () => removeBoxElement(box_id, box_array, group, items, box);
    });
}

function exceedLimitBox(current_box_dimensions, box) {
    return parseFloat(current_box_dimensions.weight) > parseFloat(box.weight)
        || parseFloat(current_box_dimensions.height) > parseFloat(box.height)
        || parseFloat(current_box_dimensions.width) > parseFloat(box.width)
        || parseFloat(current_box_dimensions.lengthBox) > parseFloat(box.length);
}

function removeBoxElement(box_index, box_array = [], container_box, items = [], box) {
    let box_removed_index;

    box_array.map((box, index) => {
        if ((parseInt(box.id) - 1) == box_index) {
            box_removed_index = index;
            box.items.map((item) => {
                items.push(item);
            });
        }
    });

    box_array.splice(box_removed_index, 1);
    console.log(items);

    container_box.remove();

    const boxes_cards = document.querySelectorAll('.ShipSmart__box-card');
    Array.from(boxes_cards).map((group, index) => {
        const box_id = group.getAttribute('data-box-id'); 
        const box_select = group.querySelector('select');
        const item_button_plus = group.querySelector(`.ShipSmart__box-button`);
        const remove_box = group.querySelector(`.ShipSmart__box-remove`);
        box_select.innerHTML = '';
        renderSelect(box_select, items);
        item_button_plus.onclick = () => addItemInBox(box_id, box_array, items, box);
        remove_box.onclick = () => removeBoxElement(box_id, box_array, container_box, items, box);
    });
}

export {
    createBoxCard,
    removeItemInBox,
    exceedLimitBox,
    removeBoxElement
}
