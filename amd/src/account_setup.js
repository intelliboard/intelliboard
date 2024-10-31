// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 *
 * @package    local_intelliboard
 * @copyright  2019 IntelliBoard, Inc
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    https://www.intelliboard.net/
 */


define(['jquery', 'core/ajax', 'core/log'], function($, ajax, log) {

    const ASValidator = {
        required: (input) => {
            return input.value.length > 0;
        },
        email: (input) => {
            let pattern = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
            return pattern.test(input.value);
        },
        tel: (input) => {
            let pattern = /^[+]*[(]{0,1}[0-9]{1,3}[)]{0,1}[-\s\./0-9]*$/;
            return pattern.test(input.value);
        },
        validate: (input) => {
            if (input.required) {
                if (typeof ASValidator[input.type] === "function") {
                    return ASValidator[input.type](input)
                } else {
                    return ASValidator.required(input);
                }
            }
        }
    }

    const AccountSetup = {
        forms: ["getstartedform", "accountform", "accounttypeform", "usertypeform"],

        validator: ASValidator,
        usertype: [],

        init: (setup) => {
            if (setup == true) {
                AccountSetup.forms = ["getstartedform", "thanksform"];
            } else {
                AccountSetup.initFormValidation("accountform");
                AccountSetup.initFormValidation("accounttypeform");
                AccountSetup.initAccountTypeSelection();
                AccountSetup.initUserTypeSelection();
                AccountSetup.setupSubmitAction();
            }
            AccountSetup.initNextAction();
            AccountSetup.initPrevAction();
        },
        getPrevForm: (currentForm) => {
            let pos = AccountSetup.forms.indexOf(currentForm);
            let prevForm = AccountSetup.forms[pos - 1];
            return prevForm.length ? prevForm : false;
        },
        getNextForm: (currentForm) => {
            let pos = AccountSetup.forms.indexOf(currentForm);
            let nextForm = AccountSetup.forms[pos + 1];
            return nextForm.length ? nextForm : false;

        },
        toggleForms: (showForm, hideForm) => {
            document.getElementById(hideForm).classList.add("intelliboard-hide");
            document.getElementById(showForm).classList.remove("intelliboard-hide");
        },
        getFormInputs: (formId) => {
            let form = document.getElementById(formId);
            return form.querySelectorAll('input, select');
        },
        setValidationState: (input, valid) => {
            if (valid) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
            }
        },
        initValidationAction: (inputs) => {
            inputs.forEach((input) => {
                if (input.required) {
                    let event = 'input';
                    if (input.type === 'select') {
                        event = 'change';
                    }
                    input.addEventListener(event, function () {
                            AccountSetup.setValidationState(input, AccountSetup.validator.validate(input));
                    });
                }
            });
        },
        setHelpTextState: (formId, visible) => {
            let helptext = document.getElementById(formId).getElementsByClassName('form-help-text')[0];
            if (helptext) {
                if (visible) {
                    helptext.classList.replace('invisible', 'visible');
                } else {
                    helptext.classList.replace('visible', 'invisible');
                }
            }
        },
        setNextButtonState: (formId, disabled) => {
            let nextbutton = document.getElementById(formId).getElementsByClassName('next-btn')[0];
            if (nextbutton) {
                nextbutton.disabled = disabled;
                AccountSetup.setHelpTextState(formId, disabled);
            }
        },

        isFormValid: (formId) => {
            let formInputs = AccountSetup.getFormInputs(formId);
            for (let i = 0; i < formInputs.length; i++)  {
                if (formInputs[i].required && !AccountSetup.validator.validate(formInputs[i])) {
                    return false;
                }
            }
            return true;
        },
        initFormValidation: (formId) => {
            let formInputs = AccountSetup.getFormInputs(formId);
            AccountSetup.initValidationAction(formInputs);
            for (let i = 0; i < formInputs.length; i++)  {
                let event = 'input';
                if (formInputs[i].type === 'select') {
                    event = 'change';
                }
                formInputs[i].addEventListener(event, function () {
                    AccountSetup.setNextButtonState(formId, !AccountSetup.isFormValid(formId));
                });
            }
        },
        initNextAction: () => {
             document.getElementsByClassName("next-btn").forEach((button) => {
                button.addEventListener('click', function () {
                    let currentform = this.getAttribute('data-form');
                    let nextform = AccountSetup.getNextForm(currentform);
                    AccountSetup.toggleForms(nextform, currentform);
                    let inputs = AccountSetup.getFormInputs(nextform);
                    if (inputs[0]) {
                        inputs[0].dispatchEvent(new Event("input"));
                    }
                    return false;
                }, false);
            });
        },
        initPrevAction: () => {
            document.getElementsByClassName("prev-btn").forEach((button) => {
                button.addEventListener('click', function() {
                    let currentform = this.getAttribute('data-form');
                    let prevform = AccountSetup.getPrevForm(currentform);
                    AccountSetup.toggleForms(prevform, currentform);
                    return false;
                }, false);
            });
        },
        initAccountTypeSelection: () => {
            let accounttypes = document.getElementsByClassName("accounttype");
            for (var i = 0; i < accounttypes.length; i++) {
                accounttypes[i].addEventListener('click', function() {
                    accounttypes.forEach((element) => {
                        element.classList.remove('active');
                        element.setAttribute('aria-pressed', 'false');
                    });
                    this.classList.add('active');
                    this.setAttribute('aria-pressed', 'true');
                    let accounttype = this.getAttribute('data-accounttype');
                    let input = document.getElementById('accounttype');
                    if (input.value != accounttype) {
                        AccountSetup.setupUserTypeForm(accounttype);
                    }
                    input.value = accounttype;
                    input.dispatchEvent(new Event("input"));
                    return false;
                }, false);
            }
        },
        setupUserTypeForm: (accounttype) => {
                AccountSetup.usertype = [];
                document.getElementsByClassName("usertype").forEach((ut) => {
                    ut.classList.remove('active');
                    ut.setAttribute('aria-pressed', 'false');
                });
                document.getElementById('submitdata').disabled = true;
                document.getElementsByClassName('intelliboard-user-types').forEach((form) => {
                    if (form.id === accounttype) {
                        form.classList.remove("intelliboard-hide");
                    } else {
                        form.classList.add("intelliboard-hide");
                    }
                });
        },
        initUserTypeSelection: () => {
            let usertypes = document.getElementsByClassName("usertype");
            for (var i = 0; i < usertypes.length; i++) {
                usertypes[i].addEventListener('click', function() {
                    let val = this.getAttribute('data-usertype');
                    if (AccountSetup.usertype.indexOf(val) >= 0) {
                        AccountSetup.usertype.splice(AccountSetup.usertype.indexOf(val), 1);
                        this.classList.remove('active');
                        this.setAttribute('aria-pressed', 'false');
                    } else {
                        AccountSetup.usertype.push(val);
                        this.classList.add('active');
                        this.setAttribute('aria-pressed', 'true');
                    }
                    let isFormValid = AccountSetup.usertype.length > 0;
                    document.getElementById('submitdata').disabled = !isFormValid;
                    AccountSetup.setHelpTextState('usertypeform', !isFormValid);
                    return false;
                }, false);
            }
        },
        setupSubmitAction: () => {
            let submit = document.getElementById('submitdata');
            submit.addEventListener("click", (event) => {
                let forms = document.getElementsByClassName('intelliboard-splash-page');
                let data = {};
                data.usertype = AccountSetup.usertype.toString();
                forms.forEach( (form) => {
                    form.querySelectorAll('input, select').forEach( (input) => {
                        data[input.name] = input.value;
                    });
                    if (form.id === 'thanksform') {
                        form.classList.remove("intelliboard-hide");
                    } else {
                        form.classList.add("intelliboard-hide");
                    }
                });
                AccountSetup.sendData(data);
            }, false);
        },
        sendData: (data) => {
            ajax.call([{
                methodname: 'local_intelliboard_account_setup',
                args: {
                    params: data
                }
            }]);
        }
    }

    return AccountSetup;

});