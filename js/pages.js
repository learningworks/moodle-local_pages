require(["jquery"], function($) {
    $(function() {
        $("#id_pagetype").change(function() {
            switch ($(this).val()) {
                case "form":
                    $("#fitem_id_pagelayout").hide();
                    $("#form-builder").show();
                    $("#fitem_id_emailto").show();
                    $("#fitem_id_menuname").hide();
                    $("#fitem_id_onmenu").hide();
                    $("table.history-table").show();
                    break;
                default:
                    $("table.history-table").hide();
                    $("#fitem_id_onmenu").show();
                    $("#fitem_id_pagelayout").show();
                    $("#form-builder").hide();
                    $("#fitem_id_emailto").hide();
                    $("#fitem_id_menuname").show();
            }
        });

        $(".form-addrow").click(function() {
            addrow(this);
        });

        $(".form-removerow").click(function() {
            $(this).closest(".formrow").remove();
        });

        $(".field-type").change(function() {
            if ($(this).val() == "HTML") {
                $(this).closest(".formrow").find(".field-name").animate({
                    "height": 100,
                    "overflow": "auto",
                    "padding": 5
                });
                $(this).closest(".formrow").find(".default-name").animate({
                    "height": 35,
                    "overflow": "hidden"
                });
                $(this).closest(".formrow").find(".field-required").closest(".col-sm-12").hide();
                $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").hide();

                $(this).closest(".formrow").find(".field-readsfrom").closest(".col-sm-12").hide();

                $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").addClass("col-md-8");
                $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").removeClass("col-md-2");
            } else {
                if ($(this).val() == "Select") {
                    $(this).closest(".formrow").find(".field-name").animate({
                        "height": 35,
                        "overflow": "hidden"
                    });
                    $(this).closest(".formrow").find(".default-name").animate({
                        "height": 35,
                        "overflow": "hidden"
                    });
                    $(this).closest(".formrow").find(".field-required").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".field-readsfrom").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").removeClass("col-md-4");
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").addClass("col-md-2");
                    $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").addClass("col-md-2");
                    $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").removeClass("col-md-8");
                    $(this).closest(".formrow").find(".default-name").animate({
                        "height": 100,
                        "overflow": "hidden",
                        "padding": 5
                    });
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").addClass("col-md-4");
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").removeClass("col-md-2");
                    $(this).closest(".formrow").find(".field-readsfrom").closest(".col-sm-12").hide();
                } else {
                    $(this).closest(".formrow").find(".field-name").animate({
                        "height": 35,
                        "overflow": "hidden"
                    });
                    $(this).closest(".formrow").find(".default-name").animate({
                        "height": 35,
                        "overflow": "hidden",
                    });
                    $(this).closest(".formrow").find(".field-required").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".field-readsfrom").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").removeClass("col-md-4");
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").addClass("col-md-2");
                    $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").addClass("col-md-2");
                    $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").removeClass("col-md-8");
                }
            }
        });

        $("table.history-table").hide();
        $("#fitem_id_onmenu").show();
        $("#fitem_id_menuname").show();
        $("#fitem_id_emailto").hide();
        $("#fitem_id_pagelayout").show();
        $("#form-builder").hide();
        $(".field-type").trigger("change");
        $("#id_pagetype").trigger("change");

        $("#showform-builder").click(function(e) {
            e.preventDefault();
            $(".formbuilderform").toggle("fast");
            $("#showEdit").toggle("fast");
            $("#hideEdit").toggle("fast");
            return false;
        });
        $("#showEdit").toggle("fast");
        $("#showform-builder").trigger("click");
    });

    /**
     * Add a new row
     * @param {Object} item the dom object passed.
     */
    function addrow(item) {
        var newrow = $(item).closest(".formrow").clone();
        $(newrow).appendTo(".formbuilderform");
        $(newrow).find("select").css({"display": "block"});

        $(newrow).find("select").each(function() {
            $(this).val("");
            $(this).find('option:first-child').attr("selected", "selected");
        });
        $(newrow).find("textarea").each(function() {
            $(this).val("");
        });
        $(".form-removerow").click(function() {
            $(this).closest(".formrow").remove();
        });
        $(".form-addrow").click(function() {
            $(this).unbind();
            addrow(this);
        });

        $(newrow).find(".field-type").change(function() {
            if ($(this).val() == "HTML") {
                $(this).closest(".formrow").find(".field-name").animate({
                    "height": 100,
                    "overflow": "auto",
                    "padding": 5
                });
                $(this).closest(".formrow").find(".default-name").animate({
                    "height": 35,
                    "overflow": "hidden"
                });
                $(this).closest(".formrow").find(".field-required").closest(".col-sm-12").hide();
                $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").hide();
                $(this).closest(".formrow").find(".field-readsfrom").closest(".col-sm-12").hide();
                $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").addClass("col-md-8");
                $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").removeClass("col-md-2");
            } else {
                if ($(this).val() == "Select") {
                    $(this).closest(".formrow").find(".field-name").animate({
                        "height": 35,
                        "overflow": "hidden"
                    });
                    $(this).closest(".formrow").find(".default-name").animate({
                        "height": 35,
                        "overflow": "hidden"
                    });
                    $(this).closest(".formrow").find(".field-required").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".field-readsfrom").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").removeClass("col-md-4");
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").addClass("col-md-2");
                    $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").addClass("col-md-2");
                    $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").removeClass("col-md-8");

                    $(this).closest(".formrow").find(".default-name").animate({
                        "height": 100,
                        "overflow": "hidden",
                        "padding": 5
                    });
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").addClass("col-md-4");
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").removeClass("col-md-2");
                    $(this).closest(".formrow").find(".field-readsfrom").closest(".col-sm-12").hide();
                } else {
                    $(this).closest(".formrow").find(".field-name").animate({
                        "height": 35,
                        "overflow": "hidden",
                    });
                    $(this).closest(".formrow").find(".default-name").animate({
                        "height": 35,
                        "overflow": "hidden"
                    });
                    $(this).closest(".formrow").find(".field-required").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".field-readsfrom").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").show();
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").removeClass("col-md-4");
                    $(this).closest(".formrow").find(".default-name").closest(".col-sm-12").addClass("col-md-2");
                    $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").addClass("col-md-2");
                    $(this).closest(".formrow").find(".field-name").closest(".col-sm-12").removeClass("col-md-8");
                }
            }
        });
        $(newrow).find(".field-type").val("Text");
        $(newrow).find(".field-type").trigger("change");
    }
});