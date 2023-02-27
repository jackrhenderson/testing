document.querySelector("#toggle-password-visibility button").addEventListener("click", function(){
    var x = document.querySelector("#toggle-password-visibility input");
    if (x.type === "password") {
        x.type = "text";
        document.querySelector("#toggle-password-visibility i").classList.add("fa-eye-slash");
        document.querySelector("#toggle-password-visibility i").classList.remove("fa-eye");
    } else {
        x.type = "password";
        document.querySelector("#toggle-password-visibility i").classList.add("fa-eye");
        document.querySelector("#toggle-password-visibility i").classList.remove("fa-eye-slash");
    }
});