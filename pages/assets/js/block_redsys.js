var last_request = [];
(() => {
    "use strict";

    const name = "redsys";
    const paymentFieldsUrl = "/?wc-api=WC_redsys_payment_fields";

    const {registerPaymentMethod} = window.wc.wcBlocksRegistry;
    const {getPaymentMethodData} = window.wc.wcSettings;
    const {decodeEntities} = wp.htmlEntities;
    const {createElement} = window.React;
    const {checkoutData} = window.wc.wcSettings.allSettings;

    const paymentMethodData = getPaymentMethodData(name);

    class Content extends window.React.Component{
        componentDidMount(){
            if(this.paymentFields){
                jQuery('#div-' + name).html(this.paymentFields)
            }
        }

        render(){
            this.paymentFields = getPaymentFields();
            if(this.paymentFields){
                return createElement('div', { 
                    id: 'div-' + name
                });
            }
            return decodeEntities(paymentMethodData.description || "");
        }
    }

    const options = {
        name: name,
        label: decodeEntities(paymentMethodData.title || ""),
        ariaLabel: decodeEntities(paymentMethodData.title || ""),
        placeOrderButtonLabel: decodeEntities(paymentMethodData.buttonLabel || ""),
        content: createElement(Content, null),
        edit: createElement(Content, null),
        canMakePayment: () => true,
        supports: {
            features: paymentMethodData.supports
        }
    };

    registerPaymentMethod(options);

    function getPaymentFields(){
        if(!paymentFieldsUrl){
            return "";
        }
        const post_data = {
            order_id: checkoutData.order_id,
            billing_first_name: checkoutData.billing_address.first_name,
            billing_last_name: checkoutData.billing_address.last_name,
            billing_company: checkoutData.billing_address.company,
            billing_country: checkoutData.billing_address.country,
            billing_address_1: checkoutData.billing_address.address1,
            billing_address_2: checkoutData.billing_address.address2,
            billing_postcode: checkoutData.billing_address.postcode,
            billing_city: checkoutData.billing_address.city,
            billing_state: checkoutData.billing_address.state,
            billing_phone: checkoutData.billing_address.phone,
            billing_email: checkoutData.billing_address.email,
        }

        var data = {
            post_data: new URLSearchParams(post_data).toString()
        };

        const full_url = paymentFieldsUrl + "?" + data.post_data;
        if(!last_request[full_url]){
            var jqXHR = jQuery.ajax({
                type:		'POST',
                url:		paymentFieldsUrl,
                data:		data,
                async:      false,
            });

            last_request[full_url] = jqXHR.status == 200 ? jqXHR.responseText : ""
        }

        return last_request[full_url];
    }
})();