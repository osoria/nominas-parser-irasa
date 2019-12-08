$( document ).ready(function () {
    $("#nomina_pdf_mode").change(function () {
        if ($(this).val() == 'selec') {
            $(".employes").show();
        } else {
            $(".employes").hide();
        }
    });
});
