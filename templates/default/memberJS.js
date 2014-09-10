/**
 * Created with JetBrains PhpStorm.
 * User: oskar
 * Date: 1/3/13
 * Time: 9:35 AM
 * To change this template use File | Settings | File Templates.
 */

/**
 * Created with JetBrains PhpStorm.
 * User: oskar
 * Date: 12/31/12
 * Time: 4:05 PM
 * To change this template use File | Settings | File Templates.
 */

$('documenet').ready(function(){

    init();
    $('#xsca_member_filter').keyup(function(){
        filter($(this));
    });

    $('#form_').find("input[type='SUBMIT'][name='cmd[save]']").click(function(e){
        e.preventDefault();
        addMember();
    });

    $(document).ajaxStart(function(){
        $('.xsca_loader').css("visibility", "visible");
    }).ajaxStop(function(){
            $('.xsca_loader').css("visibility", "hidden");
        });

});

//save all available options with their values and the empty option.
init = function(){
    options = new Object();
    $('#clipmember option').each(function(){
        var obj = $(this);
        if(obj.attr("value") != "" && obj.attr("value") != 0)
            options[obj.attr('value')] = obj.html();
        else
            emptyOption = obj.html();
    });
    selObj = $('#clipmember');
    $('table.il_ColumnLayout').css("width", "80%").css("clear", "none");
    $('.ilTabContentInner').append("<span class='xsca_loader'><img src='/templates/default/images/loader.gif'></span>");
};

filter = function(elem){
    var filter = elem.val();
    var selected = $('#clipmember option:selected').val();

    //delete all options and add the empty option
    selObj.html("");
    selObj.append("<option> "+emptyOption+" </option>");

    //add all options conaining the filter string
    for(value in options){
        var option = options[value];
        //if((options[value].toLowerCase()).indexOf(filter.toLowerCase()) != -1){
            selObj.append("<option value='"+value+"'> "+options[value]+" </option>");
        //}
    }

    //select the previously selected option
    $("#clipmember option[value = '"+selected+"']").prop("selected", true);
}

addMember = function(){
    var form = $('#form_');
    var link = form.attr("action");
    var selected = form.find("#clipmember option").filter(":selected").html();
    var id = form.find("#clipmember option").filter(":selected").val();

    if(id == 0 || id == "")
        return;

    $.ajax({
        type: 'POST',
        cache: false,
        url: link,
        data: form.serialize(),
        success: function(msg) {
            $('table.fullwidth').append(msg);
        }
    });

    delete options[id];

    filter($('#xsca_member_filter'));
}