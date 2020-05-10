(function ($) {    // Avoid conflicts with other libraries
    $().ready(function () {
        changeEnable($('#forum_type_ticket'), 'blockSelectedGroup');

        $('#forum_type_ticket').on('change', function () {
            changeEnable(this, 'blockSelectedGroup');
        });

        $("#group_edit").on("click", function () {
            $("#blockGroups").show();
        });

        $("#groups").on("click", function () {
            $("#groupapproval_name").val($(this).find("option:selected").text());
            $("#hGroupId").val($(this).find("option:selected").val());
        });

        function changeEnable(el, idDiv) {
            var div = $('#' + idDiv);
            if ($(el).prop('checked')) {
                $(div).show();

            }
            else {
                $(div).hide();
            }
        }


    });

})(jQuery);                                                      // Avoid conflicts with other libraries
