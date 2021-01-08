/**
 * Copyright © 2020 TkhConsult. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'kinabank_gateway',
                component: 'TkhConsult_KinaPg/js/view/payment/method-renderer/kinabank_gateway'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
