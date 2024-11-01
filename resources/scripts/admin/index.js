// eslint-disable-next-line no-unused-vars
// import config from '@config';
import '@styles/admin';
// import 'airbnb-browser-shims'; // Uncomment if needed
import { loadSettingsModal } from './settings/ship';
import {
    updateOrders,
    createBoxDimensions,
    saveBoxesDimensions,
    removeBox,
    loadBoxes
} from './settings/plugin';

import {
    createBox,
    disableSyncroButton,
    loadOrderPage,
    handleChangeBoxOption
} from './order/page';

import { removeItemInBox, removeBoxElement } from './services/box';

window.updateOrders = updateOrders;
window.createBoxDimensions = createBoxDimensions;
window.saveBoxesDimensions = saveBoxesDimensions;
window.removeBox = removeBox;

window.createBox = createBox;
window.disableSyncroButton = disableSyncroButton;
window.handleChangeBoxOption = handleChangeBoxOption;

window.removeItemInBox = removeItemInBox;
window.removeBoxElement = removeBoxElement;

// Your code goes here ...
window.onload = () => {
    if (document.location.href.includes('post.php')) {
        loadOrderPage();
    } else if (document.location.href.includes('admin.php?page=wc-settings')) {
        loadSettingsModal();
    } else if (document.location.href.includes('admin.php?page=shipsmart')) {
        loadBoxes();
    }
}
