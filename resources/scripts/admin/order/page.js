import { createBoxCard } from  '../services/box';
import { getItemsByOrderId, getBoxes, postSyncroOrder } from '../services/api';
import image from '../../../images/sprite/refresh-icon.png';

let boxes_order = [];
let boxes_created = [];
let items_order = [];
let order_id;
let note_key_hidden;
let box_count = 0;

function loadOrderPage() {
    const note_key_input = document.getElementById('note_key');
    const note_key_hidden_element = document.getElementById('ss_note_key');
    const order_shipping_table = document.querySelector('#order_shipping_line_items table');
    const woocomercer_order_id = document.getElementById('woocomercer_order_id');
    const sycro_with_shipsmart = document.getElementById('sycro_with_shipsmart');
    const order_box = document.getElementById('order_box_plus');

    note_key_input.setAttribute('maxlength', 44);

    if (order_box) {
        order_box.setAttribute('disabled', true);
    }

    note_key_hidden = note_key_hidden_element.value;
    order_id = woocomercer_order_id.value;

    getBoxes((data) => {
        boxes_created = data['boxes'];
    })

    getItemsByOrderId(async (data) => {
        items_order = data['items'];
    }, order_id);

    if (order_shipping_table) {
        document.querySelector('#order_shipping_line_items .line_cost').append(order_shipping_table);
        document.querySelector('#order_shipping_line_items .line_cost table')?.classList.add('Show');
        document.querySelector('#order_shipping_line_items .line_cost table th')?.classList.add('ShipSmart__order-meta');
    }

    if (note_key_input) {
        note_key_input.onkeyup = disableSyncroButton;
    }

    if (sycro_with_shipsmart) {
        sycro_with_shipsmart.setAttribute('disabled', true);
        sycro_with_shipsmart.onclick = syncroShipSmart;
    }
}

function syncroShipSmart() {
    const note_key = document.getElementById('note_key');
    const sycro_with_shipsmart = document.getElementById('sycro_with_shipsmart');
    const imageRefresh = document.createElement('img');
    const urlImage = `${wpApiSettings.imageRefreshUrl}${'refresh-icon.6e7225bab3.png'}`;
    console.log(urlImage);
    const boxesSelect = document.querySelector('.ShipSmart__order-boxes');

    imageRefresh.setAttribute('src', urlImage);
    imageRefresh.classList.add('ShipSmart__refresh');
    imageRefresh.classList.add('refresh-start');
    
    sycro_with_shipsmart.append(imageRefresh)

    boxes_order.map((box) => {
        box.invoiceKey = note_key.value,
        box.quantidade = box.items.length,
        box.qtdTotal = box.items.length
    });

    const data = {
        note_key: note_key.value,
        order_id: order_id,
        boxes: parseInt(boxesSelect.value) ? boxes_order : []
    };

    postSyncroOrder(data => {
        if ( data ) {
            document.location.reload();
        }
    }, data);
}

function createBox() {
    const select_boxes = document.querySelector('.ShipSmart__order-boxes');
    const boxes_content = document.getElementById('boxes_content');
    const box_id = parseInt(select_boxes.value) - 1;
    box_count++;

    const box = {
        id: box_count,
        items: [],
        price: 0,
        weight: boxes_created[box_id].weight,
        invoiceKey: '',
        quantidade: 0,
        qtdTotal: 0,
        measures: {
            height: boxes_created[box_id].height,
            width: boxes_created[box_id].width,
            depth: boxes_created[box_id].length
        },
    };

    boxes_order.push(box);

    
    createBoxCard(boxes_created[box_id], boxes_order, items_order, boxes_content, box_count - 1);
}

function disableSyncroButton(event) {
    const sycro_with_shipsmart = document.getElementById('sycro_with_shipsmart');

    if (event.target.value.length === 44 && note_key_hidden !== event.target.value) {
        sycro_with_shipsmart.removeAttribute('disabled'); 
    } else {
        sycro_with_shipsmart.setAttribute('disabled', true);
    }
}

function enableCreateBoxButton(option) {
    if (parseInt(option)) {
        document.getElementById('order_box_plus').removeAttribute('disabled');
    } else {
        document.getElementById('order_box_plus').setAttribute('disabled', true);
    }
}

function handleChangeBoxOption() {
    const select_boxes = document.querySelector('.ShipSmart__order-boxes');

    if (select_boxes) {
        enableCreateBoxButton(select_boxes.value);
    }
}

export {
    createBox,
    disableSyncroButton,
    loadOrderPage,
    handleChangeBoxOption
}
