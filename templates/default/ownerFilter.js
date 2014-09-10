/**
 * Created with JetBrains PhpStorm.
 * User: oskar
 * Date: 12/31/12
 * Time: 4:05 PM
 * To change this template use File | Settings | File Templates.
 */

$('documenet').ready(function(){
    init();
   $('#xsca_owner_filter').keyup(function(){
       filter($(this));
   });
});

//save all available options with their values and the empty option.
init = function(){
    options = new Object();
    $('#owner option').each(function(){
        var obj = $(this);
        if(obj.attr("value") != "")
            options[obj.attr('value')] = obj.html();
        else
            emptyOption = obj.html();
    });
    selObj = $('#owner');
};

filter = function(elem){
    var filter = elem.val();
    var selected = $('#owner option:selected').val();

    //delete all options and add the empty option
    selObj.html("");
    selObj.append("<option> "+emptyOption+" </option>");

    //add all options conaining the filter string
    for(value in options){
        var option = options[value];
        if((options[value].toLowerCase()).indexOf(filter.toLowerCase()) != -1){
            selObj.append("<option value='"+value+"'> "+options[value]+" </option>");
        }
    }

    //select the previously selected option
    $("#owner option[value = '"+selected+"']").prop("selected", true);
}