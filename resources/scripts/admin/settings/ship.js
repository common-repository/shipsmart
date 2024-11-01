import IMask from 'imask';

function loadSettingsModal() {
    const observer = new MutationObserver(observerModalSettings);
    observer.observe( document.querySelector('.woocommerce-page.woocommerce-embed-page') , {childList: true} );
}

function observerModalSettings(mutationList, observer) {
    for (const mutation of mutationList) {
        mutation.addedNodes.forEach( node => {
            let is_federal_tax_id = document.querySelector('#woocommerce_ss_shipping_method_is_federal_tax_id');
            
            if (!is_federal_tax_id) {
                return;
            }

            createSection();
            is_federal_tax_id = document.querySelector('#woocommerce_ss_shipping_method_is_federal_tax_id');
            const titleModal = document.querySelector('.wc-backbone-modal-header h1');
            const insurance_input = document.querySelector('#woocommerce_ss_shipping_method_insurance');
            const taxable_input = document.querySelector('#woocommerce_ss_shipping_method_taxable');
            const federal_tax_id = document.getElementById('woocommerce_ss_shipping_method_federal_tax_id')
            const phone = document.getElementById('woocommerce_ss_shipping_method_phone')
            const percentual_discount = document.querySelector('#woocommerce_ss_shipping_method_percentual_discount');
            const discount_insurance = document.querySelector('#woocommerce_ss_shipping_method_discount_insurance');
            const discount_taxable = document.querySelector('#woocommerce_ss_shipping_method_discount_taxable');
            const plus_date_final = document.querySelector('#woocommerce_ss_shipping_method_plus_date_final');
            const name = document.querySelector('#woocommerce_ss_shipping_method_name');
            const email = document.querySelector('#woocommerce_ss_shipping_method_email');
            const title = document.querySelector('#woocommerce_ss_shipping_method_title');
            const freight_title = document.querySelector('#woocommerce_ss_shipping_method_freight_title');
            const insurance_name = document.querySelector('#woocommerce_ss_shipping_method_insurance_name');
            const taxable_name = document.querySelector('#woocommerce_ss_shipping_method_taxable_name');
            const predict_taxable = document.querySelector('#woocommerce_ss_shipping_method_predict_taxable');
            const total_price_name = document.querySelector('#woocommerce_ss_shipping_method_total_price_name');
            const predict_days_name = document.querySelector('#woocommerce_ss_shipping_method_predict_days_name');
            const days_text = document.querySelector('#woocommerce_ss_shipping_method_days_text');
            const maskOptions = {
                mask: '00.000.000/0000-00'
            };
            titleModal.innerText = 'Configurações ShipSmart';
            federal_tax_id.setAttribute('maxlength', 18);
            federal_tax_id.setAttribute('placeholder', '00.000.000/0000-00');
            federal_tax_id.onkeypress = (event) => {
                return (/[0123456789,.]/.test(String.fromCharCode(event.which) ));
            }

            const maskFederalTaxId = IMask(federal_tax_id, maskOptions);
            const maskPhone = IMask(phone, {mask: '+00 (00) 00000-0000'});
            
            percentual_discount.setAttribute('min', 0);
            percentual_discount.setAttribute('max', 100);
            discount_insurance.setAttribute('min', 0);
            discount_insurance.setAttribute('max', 100);
            discount_taxable.setAttribute('min', 0);
            discount_taxable.setAttribute('max', 100);
            
            defaultValue(percentual_discount, 0);
            defaultValue(discount_insurance, 0);
            defaultValue(discount_taxable, 0);
            defaultValue(plus_date_final, 0);
            defaultValue(federal_tax_id, '00000000000000');
            defaultValue(name, 'Ship Smart Seller');
            defaultValue(phone, '11111111111');
            defaultValue(email, 'shipsmart@config.com');
            defaultValue(title, 'Frete ShipSmart');
            defaultValue(freight_title, 'Valor frete');
            defaultValue(insurance_name, 'Taxa do seguro');
            defaultValue(taxable_name, 'Outras taxas');
            defaultValue(predict_taxable, 'Previsão da taxa (não cobrado na compra)');
            defaultValue(total_price_name, 'Total do frete');
            defaultValue(predict_days_name, 'Seu produto será entregue em');
            defaultValue(days_text, 'dias');


            hiddenViewTaxable(taxable_input.checked);
            hiddenInputFederalTaxID(is_federal_tax_id.checked);
            hiddenInsuranceDiscount(insurance_input.checked)

            if (taxable_input) {
                taxable_input.addEventListener('click', e => {
                    hiddenViewTaxable(taxable_input.checked);
                }, true);
            }

            if (insurance_input) {
                insurance_input.addEventListener('click', e => {
                    hiddenInsuranceDiscount(insurance_input.checked);
                }, true);
            }

            if (is_federal_tax_id) {
                is_federal_tax_id.addEventListener('click', e => {
                    hiddenInputFederalTaxID(is_federal_tax_id.checked);
                }, true);
            }

        } );
    }
}

function createSection() {
    const rows = document.querySelectorAll('.wc-backbone-modal-shipping-method-settings .form-table tr');
    const formTable = document.querySelector('.wc-backbone-modal-shipping-method-settings .form-table tbody');
    const tbodyForm = document.createElement('tbody');
    const titleInitial = document.createElement('H2');

    titleInitial.innerText = 'Dados da loja';
    titleInitial.classList.add('ShipSmart__section');
    
    tbodyForm.appendChild(titleInitial);

    formTable.innerHTML = Array.from(rows).reduce((carry, row, index) => {
        const title = document.createElement('H2');
        title.classList.add('ShipSmart__section');
        if (index === 5) {
            title.innerText = 'Taxas';
            carry.appendChild(title);
        } else if (index === 12) {
            title.innerText = 'Prazo adicional';
            carry.appendChild(title);
        } else if (index === 13) {
            title.innerText = 'Renomear rótulos';
            carry.appendChild(title);
        }

        carry.appendChild(row);

        return carry;
    }, tbodyForm).innerHTML;
}

function hiddenViewTaxable(checked) {
    const rows = document.getElementsByTagName('tr');
    Array.from(rows).map(row => {
        if (row.querySelector('#woocommerce_ss_shipping_method_view_taxable')) {
            if (checked) {
                row.classList.add('Hidden');
            } else {
                row.classList.remove('Hidden');
            }
        } else if (row.querySelector('#woocommerce_ss_shipping_method_discount_taxable')) {
            if (checked) {
                row.classList.remove('Hidden');
            } else {
                row.classList.add('Hidden');
            }
        }
    });
}

function hiddenInputFederalTaxID(checked) {
    const rows = document.getElementsByTagName('tr');
    Array.from(rows).map(row => {
        if (row.querySelector('#woocommerce_ss_shipping_method_federal_tax_id')) {
            if (checked) {
                row.classList.remove('Hidden');
            } else {
                row.classList.add('Hidden');
            }
        }
    });
};

function hiddenInsuranceDiscount(checked) {
    const rows = document.getElementsByTagName('tr');
    Array.from(rows).map(row => {
        if (row.querySelector('#woocommerce_ss_shipping_method_discount_insurance')) {
            if (checked) {
                row.classList.remove('Hidden');
            } else {
                row.classList.add('Hidden');
            }
        }
    });
};

function defaultValue(element, value) {
    if (!element.value) {
        element.value = value;
    }
}


export {
    loadSettingsModal
}