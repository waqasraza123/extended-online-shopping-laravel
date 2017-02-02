
/**
 * First we will load all of this project's JavaScript dependencies which
 * include Vue and Vue Resource. This gives a great starting point for
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the body of the page. From here, you may begin adding components to
 * the application, or feel free to tweak this setup for your needs.
 */

Vue.component('example', require('./components/Example.vue'));

Vue.component(
    'passport-clients',
    require('./components/passport/Clients.vue')
);

Vue.component(
    'passport-authorized-clients',
    require('./components/passport/AuthorizedClients.vue')
);

Vue.component(
    'passport-personal-access-tokens',
    require('./components/passport/PersonalAccessTokens.vue')
);

const app = new Vue({
    el: '#app',

    method: {
        registerUser: function () {
            alert("got you");
        }
    }/*methods of vue ends*/
});

/*when the webpage's elemenents are ready*/
$(document).ready(function () {

    /**
     *register the user
     */
    $("#register_user").click(function (event) {
        event.preventDefault()

        //send the post request to save the user
        $.ajax({
            data: $("#user_register_form").serialize(),
            type: 'post',
            url: '/register',
            success: function (data) {
                console.log("this was supposed to be success" + data)
            },
            error: function (error) {
                $('div.alert-danger').show();
                $.each(error, function (index, error) {
                    $('div.alert-danger ul').push('<li>'+ error +'</li>')
                })
            }
        })
    })

    /**
     * register the shop information
     */
    $("#register_shop").click(function (event) {
        event.preventDefault()

        //send the post request to save the user
        $.ajax({
            data: $("#shop_register_form").serialize(),
            type: 'post',
            url: '/register-shop',
            success: function (data) {
                window.location = '/home'
            },
            error: function (error) {
                console.log("error" + error)
            }
        })
    })

    /**
     * hide the error or success notification
     */
    if(($(".alert")).length > 0){
        $(".alert").delay(4000).slideUp(400)
    }
})