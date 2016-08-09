var url = "home.php";
var errMsg = "";
var result;

checkLogIn();

$(document).ready(function () {

    $(".toggleBtn").click(function () {

        $("fieldset").toggle();

    });

    $("#sb_signup").click(function (e) {

        $(this).attr("disabled", true);

        e.preventDefault(); //prevent immedate submission
        errMsg = "";

        if (!validate("signUp")) {

            updateMsg();

        } else {

            updateMsg();

            var signUp = $.ajax({
                url: url,
                type: "post",
                async: true,
                data: {

                    mode: "signUp",
                    usrName_new: $("#usrName_new").val(),
                    email: $("#email").val(),
                    psd_new: $("#psd_new").val(),
                    repsd_new: $("#repsd_new").val(),

                }
            });

            signUp.done(function (data, status) {

                result = eval('(' + data + ')');

                if (result.message == "") {

                    unknown();

                } else {

                    if (result.token === "success") {
                        errMsg = '<div class="alert alert-info" role="alert">' + result.message + '</div>';
                        updateMsg();
                        setTimeout(function () {
                            complete(result);
                        }, 2000);

                    } else {

                        errMsg = '<div class="alert alert-danger" role="alert">' + result.message + '</div>';
                        updateMsg();
                        updateValue(result);

                    }
                }
            });

            signUp.fail(function (xhr) {
                alert("Request fail");
                unknown();
            });

        }

        $(this).attr("disabled", false);

    });

    $("#sb_signin").click(function (e) {

        e.preventDefault();
        $(this).attr("disabled", true); // disable the button

        if (!validate("signIn")) {

            updateMsg();

        } else {

            updateMsg();
            var logIn = $.ajax({
                url: url,
                type: "post",
                async: true,
                data: {

                    mode: "signIn",
                    usrName: $("#usrName").val(),
                    psd: $("#psd").val(),
                    keep: $("#keep").val(),

                }
            });

            logIn.done(function (data) {

                result = eval('(' + data + ')');
                if (result.token == "success") {

                    window.location.href = "mynotes.php";

                } else {

                    errMsg = '<div class="alert alert-danger alert-dismissable" role="alert">' + result.message + '</div>';
                    updateMsg();

                }

            });

            logIn.fail(function (xhr) {
                alert("Request fail");
                unknown();
            });

        }

        $(this).attr("disabled", false);

    });

    $("#sb_signon").click(function (e) {

        e.preventDefault();
        $(this).attr("disabled", true);

        var signOn = $.ajax({
            url: url,
            type: "post",
            async: true,
            data: {

                mode: "signIn",
                usrName: $("#usrName").val(),
                psd: $("#psd").val(),
                keep: $("#keep").val(),
                pass: "YES",

            }
        });

        signOn.success(function () {

            window.location.href = "mynotes.php"

        });

    });

});

function checkLogIn() {

    var logIn = $.ajax({
        url: url,
        type: "post",
        async: true,
        data: {

            mode: "check",

        }
    });

    logIn.done(function (data) {

        result = eval('(' + data + ')');
        if (result.token == "success") {

            errMsg = '<div class="alert alert-info alert-dismissable" role="alert">' + result.message + '</div>';
            updateMsg();
            complete(result);

        }

    });

}

function complete(result) {

    if (result.status == "Kept") {
        $("#keep").attr("disabled", true).attr("checked", true);

    }
    $("#signup").hide();
    $("#signin").show();
    $("#newUsrTag").hide();
    $("#psd").hide();
    $("#usrName").attr("disabled", true);
    $("#signOn").show();
    $("#sb_signin").hide();
    signInMode = "pass";
    errMsg = '<div class="alert alert-info alert-dismissable" role="alert">Welcome back, ' + result.tempUsrName + '.<br/>Please click the button to sign on.</div>';
    updateMsg();
    updateValue(result);

}

function validate(mode) {

    if (((($("#usrName").val() == "" || $("#psd").val() == "")) && mode == "signIn") ||
		(($("#email").val() == "" || $("#usrName_new").val() == "" || $("#psd_new").val() == "" || $("#repsd_new").val() == "") && mode == "signUp")) {
        errMsg = '<div class="alert alert-warning" role="alert">All fields are required!</div>';// set the error messages
        return false;
    } else if ($("#psd_new").val() == $("#repsd_new").val()) {
        errMsg = "";
        return true;
    } else {
        errMsg = '<div class="alert alert-warning alert-dismissable" role="alert">Passwords mismatch!</div>';
        return false;
    }
}

function unknown() {

    errMsg = '<div class="alert alert-warning alert-dismissable" role="alert">Unknown error.</div>';
    updateMsg();

}

function updateValue(result) {

    $("#usrName").attr("value", result.tempUsrName);

}

function updateMsg() {

    $("#err").html(errMsg);

}