(function ( ) {

jQuery(function ($) {
    $(document).ajaxError(function () {
        if (typeof window._depayUnmountLoading == "function") {
            window._depayUnmountLoading();
        }
    });

    $(document).ajaxComplete(function () {
        if (typeof window._depayUnmountLoading == "function") {
            window._depayUnmountLoading();
        }
    });

    $("form.edd-blocks-form__purchase").on("submit", async () => {
        var values = $("form.edd-blocks-form__purchase").serialize();
        console.log("values",values);
        if (values.match("edd-gateway=edd_unuspay_gateway")) {
            let { unmount } = await DePayWidgets.Loading({
                text: "Loading payment data...",
            });
            setTimeout(unmount, 10000);
        }
    });
});


const displayCheckout = async () => {
    if (window.location.hash.startsWith("#edd-unuspay-checkout-")) {
        const checkoutId = window.location.hash.match(
            /edd-unuspay-checkout-(.*?)@/
        )[1];
        const response = JSON.parse(
            await wp.apiRequest({
                path: `/unuspay/edd/checkouts/${checkoutId}`,
                method: "POST",
            })
        );
        if (response.redirect) {
            window.location = response.redirect;
            return;
        }
        const paymentInfo = [];
        response.tokens.forEach((token) => {
            paymentInfo.push({
                blockchain: token.blockchain,
                amount: token.amount,
                token: token.tokenAddress,
                receiver: token.receiveAddress,
                fee: {
                    amount: token.feeRate + "%",
                    receiver: token.feeAddress,
                },
            });
        });
        let configuration = {
            accept: paymentInfo,
            closed: () => {
                window.location.hash = "";
                window.location.reload(true);
            },
            track: {
                id: checkoutId,
                endpoint: "/wp-json/unuspay/edd/track",
                poll: {
                    endpoint: "/wp-json/unuspay/edd/release"
                }

            }
            /* track: {
                method: (payment) => {
                    return new Promise((resolve, reject) => {
                        try {
                            payment.id=checkoutId
                            wp.apiRequest({
                                path: `/unuspay/edd/checkouts/track`,
                                method: "POST",
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                data: JSON.stringify(payment),
                                dataType: 'json'
                            })
                                .done(() => resolve({ status: 200 }))
                                .fail((request, status) => reject(status));
                        } catch {
                            reject();
                        }
                    });
                },
                poll: {
                    method: (payment) => {
                        return new Promise((resolve, reject) => {
                            payment.id=checkoutId
                            wp.apiRequest({
                                path: "/unuspay/edd/release",
                                method: "POST",
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                data: JSON.stringify(payment),
                                dataType: 'json',
                            })
                                .done((responseData) => {
                                    resolve(responseData);
                                })
                                .fail(resolve);
                        });
                    },
                },
            }, */
        };

        DePayWidgets.Payment(configuration);
    }
};

document.addEventListener('DOMContentLoaded', displayCheckout);
window.addEventListener('hashchange', displayCheckout);

})()
