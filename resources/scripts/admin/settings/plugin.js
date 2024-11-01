import { putUpdateOrders, postBoxesDimensions, getBoxes } from '../services/api';

let boxes_creates = [];

async function updateOrders() {
    const shipTitle = document.querySelector('.Shipsmart__settings-title');
    const wrap = document.querySelector('.wrap');

    if (wrap.querySelector('.notice')) {
        wrap.querySelector('.notice').remove();
    }

    putUpdateOrders((response) => {
        if (response) {
            shipTitle.innerHTML += '<div class="notice notice-success is-dismissible"><p>Pedidos atualizados com sucesso!</p></div>'; 
        } else {
            shipTitle.innerHTML += '<div class="notice notice-error is-dismissible"><p>Houve algum problema na atualização!</p></div>'; 
        }
    });
} 

function createBoxDimensions() {
    const box_weight = document.getElementById('box_weight').value;
    const box_height = document.getElementById('box_height').value;
    const box_width = document.getElementById('box_width').value;
    const box_length = document.getElementById('box_length').value;
    const select_boxes = document.querySelector('.Shipsmart__settings-select');
    const option_name = `${box_weight}kg - ${box_height}cm - ${box_width}cm - ${box_length}cm`;
    let option_index;
    let box_option;

    boxes_creates.push({
        weight: box_weight,
        height: box_height,
        width: box_width,
        length: box_length
    });

    option_index = boxes_creates.length - 1;
    box_option = new Option(option_name, option_index);
    select_boxes.append(box_option);

    select_boxes.options[option_index].selected = true;
}

async function saveBoxesDimensions() {
    postBoxesDimensions((response) => {
        if (response) {
            document.location.reload();
        }
    }, boxes_creates);
}

function removeBox() {
    const select_boxes = document.querySelector('.Shipsmart__settings-select');
    boxes_creates.splice(select_boxes.options.selectedIndex, 1);
    select_boxes.innerHTML = '';
    update_box_select();
}

function loadBoxes() {
    getBoxes((data) => {
        boxes_creates = data['boxes'];
        update_box_select();
    });
}

function update_box_select() {
    const select_boxes = document.querySelector('.Shipsmart__settings-select');
    if (boxes_creates) {
        boxes_creates.map(box =>  {
            const box_weight = box['weight'];
            const box_height = box['height'];
            const box_width = box['width'];
            const box_length = box['length'];
            const option_name = `${box_weight}kg - ${box_height}cm - ${box_width}cm - ${box_length}cm`;

            const box_option = new Option(option_name);
            select_boxes.append(box_option);
        });
    } else {
        const box_option = new Option('Nenhuma caixa cadastrada', index);
        select_boxes.append(box_option);
    }
}

export {
    updateOrders,
    createBoxDimensions,
    saveBoxesDimensions,
    removeBox,
    loadBoxes
}