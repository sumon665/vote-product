jQuery(document).ready(function($) {
     
    var purbtn = $("#cus-purchase").text();

    if (purbtn==="disable") {
        $("#vote-content").css({ display: 'block' });
    }

     $("#vote_btn").click(function(e) {
        e.preventDefault();
        $(this).text("Loading...");
        
        var pid = $("#pid").val();

        $.ajax({
            url: ajax_object.ajax_url, // or example_ajax_obj.ajaxurl if using on frontend
            data: {
                'action': 'submit_vote_request',
                'pid' : pid
            },
            success: function (data) {
                $("#vote_btn").text("VOTED");
                $("#vote_btn").prop("disabled",true);
           }
        });
    });
});
