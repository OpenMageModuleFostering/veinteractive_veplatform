var mDataProcessor = mDataProcessor || {};

mDataProcessor = (function (window, document) {
    'use strict';

    var addEvent = function(evnt, elem, func) {
        if (elem.addEventListener) { // W3C compatibility
            elem.addEventListener(evnt, func, false);
        }
        else if (elem.attachEvent) { // IE compatibility
            elem.attachEvent("on" + evnt, func);
        }
        else { // No much to do
            elem[ evnt ] = func;
        }
    };

    /**
     *
     * @param {type} elems
     * @param {string|array} types
     * @param {type} classes
     * @param {type} ids
     * @returns {Array}
     */
    var getElements = function(tag, expr) {
        var responseElems = [];
        var elems = [];
        var pattern = new RegExp(expr);
        tag.forEach(function (val) {
            elems.push(document.getElementsByTagName(val));
        });

        for (var i = 0; i < elems.length; i++) {
            for (var z = 0; z < elems[i].length; z++) {
                if (pattern.test(elems[i][z].name)
                    || pattern.test(elems[i][z].className)
                    || pattern.test(elems[i][z].id)
                    || pattern.test(elems[i][z].type)) {
                    responseElems.push(elems[i][z]);
                }
            }
        }

        return responseElems;
    };

    var captureEmailsValues = function() {
        var tag = ['input'];
        var elems = getElements(tag, /text|mail/igm);

        for (var i = 0; i < elems.length; i++) {
            addEvent('keyup', elems[i], function (currentEvent) {
                setNameEmail(this);
            });
            addEvent('click', elems[i], function (currentEvent) {
                setNameEmail(this);
            });
            addEvent('blur', elems[i], function (currentEvent) {
                setNameEmail(this);
            });
        }

        for (var i = 0; i < elems.length; i++) {
            setNameEmail(elems[i]);
        }

    };

    var setNameEmail = function(a) {
        if (typeof masterData !== 'undefined') {
            if (checkEmailAdress(a.value)) {
                masterData.user.email = a.value;
            } else if ((a.value).trim().length > 0){
                var fnameFieldNames = ['firstname', 'middlename', 'billing[firstname]', 'billing[middlename]'],
                    lnameFieldNames = ['lastname', 'billing[lastname]'];
                if( fnameFieldNames.indexOf(a.name) != -1) {
                    if (a.name == 'firstname' || a.name == 'middlename') {
                        var fName = document.getElementsByName("firstname"),
                            mName = document.getElementsByName("middlename");
                    } else {
                        var fName = document.getElementsByName("billing[firstname]"),
                            mName = document.getElementsByName("billing[middlename]");
                    }
                    if (fName.length > 0) {
                        fName = fName[0].value;
                    }
                    if (mName.length > 0) {
                        mName = mName[0].value;
                    }

                    masterData.user.firstName = (fName + ' ' + mName).trim();
                } else if(lnameFieldNames.indexOf(a.name) != -1) {
                    masterData.user.lastName = a.value.trim();
                }
            }
        }
    };

    var checkEmailAdress = function(email) {
        var pattern = new RegExp(/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/);
        return pattern.test(email);
    };

    /**
     * In Magento 1.9 versions the $ was $j so in any ajax call we should check if VEjQuery is in there to use,
     * instead other jQuery Objects.
     *
     * @param {type} step
     * @returns {undefined}
     */
    var updateCart = function(step) {
        step = step || 1;
        if (step <= 5) {

            var a = $j.ajax({
                type: 'POST',
                url: baseDir + 'masterdata/index/updateCart',
                data: 'method=updateCart',
                dataType: 'json',
                success: function (data) {
                    if (masterData.cart.has_cart != 'false') {
                        var now = new Date(masterData.cart.date_upd);
                        var upd = new Date(data.date_upd);
                        if (now < upd) {
                            masterData.cart = data;

                        } else {
                            step++;
                            updateCart(step);
                        }
                    } else {
                        masterData.cart = data;

                    }
                }
            });
        }
    };


    var captureCartUpdateButtons = function() {
        if (typeof productAddToCartForm === "undefined" || !productAddToCartForm instanceof Object) {
            var tag = ['button', 'a'];
            var elems = getElements(tag, /submit|remove_link|cart/igm);

            for (var i = 0; i < elems.length; i++) {
                addEvent('click', elems[i], function (currentEvent) {
                    updateCart();
                });
            }

        } else {

            var oldUpdateCart = productAddToCartForm.submit;
            productAddToCartForm.submit = function (button, url) {
                oldUpdateCart(button, url);
                updateCart();
            };


        }
    };

    return {
        captureCartUpdateButtons: captureCartUpdateButtons,
        captureEmailsValues: captureEmailsValues,
        updateCart: updateCart
    };

}(window, document));

window.onload = function (onloadEvent) {
    
    mDataProcessor.captureEmailsValues();
    mDataProcessor.captureCartUpdateButtons();
};