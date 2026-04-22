/**
 * Orange Money Blocks Integration
 */

const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;
const { decodeEntities } = window.wp.htmlEntities;
const { createElement } = window.wp.element;

const settings = getSetting('orange_money_data', {});

const label = decodeEntities(settings.title) || 'Orange Money';

const Content = () => {
    return createElement('div', { className: 'wc-block-components-payment-method-content' },
        decodeEntities(settings.description || 'Payez en toute sécurité avec Orange Money')
    );
};

const Label = () => {
    return createElement('span', { className: 'wc-block-components-payment-method-label' },
        label
    );
};

const OrangeMoneyPaymentMethod = {
    name: 'orange_money',
    label: createElement(Label, null),
    content: createElement(Content, null),
    edit: createElement(Content, null),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports || ['products'],
    },
};

registerPaymentMethod(OrangeMoneyPaymentMethod);
