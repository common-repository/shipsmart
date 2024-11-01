// import wp from 'wp';

const putUpdateOrders = (callback) => {
    wp.apiRequest(
        {
            path: '/shipsmart/v1/updateOrders',
            method: 'PUT',
        }   
    ).then((data) => callback(data));
}

const postBoxesDimensions = (callback, boxesDimenions) => {
    wp.apiRequest(
        {
            path: '/shipsmart/v1/saveBoxes',
            method: 'POST',
            data: {
                'measures' : boxesDimenions
            },
        }
    ).then((data) => callback(data));
}

const getBoxes = (callback) => {
    wp.apiRequest(
        {
            path: '/shipsmart/v1/boxes',
            method: 'GET',
        }
    ).then((data) => callback(data));
}

const getItemsByOrderId = async (callback, order_id) => {
    wp.apiRequest(
        {
            path:`/shipsmart/v1/order/items?order_id=${order_id}`,
            method: 'GET',
        }
    ).then((data) => callback(data));
}

const postSyncroOrder = (callback, data) => {
    wp.apiRequest(
        {
            path: '/shipsmart/v1/sycroOrder',
            method: 'POST',
            data: data,
        }
    ).then((data) => callback(data));
}

export {
    putUpdateOrders,
    postBoxesDimensions,
    getBoxes,
    getItemsByOrderId,
    postSyncroOrder
}
