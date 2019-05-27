
$(document).ready(function() {
    
    /* Lesson list from goone API */
    $(".lesson-list-select").select2({
        placeholder: "Search lessons"
    });

    /* Set default value on Edit goone page*/
    var selected = $("input[name='lesson_id']" ).val();
    if(selected !== null){
        $('.lesson-list-select').val(selected.split(',')).trigger('change.select2');
    }
    

    // console.log(selected);

    // $('.lesson-list-select').val(selected); 
    // $('.lesson-list-select').trigger('change');

    if(!selected == ''){ 
        //On the edit page, hide grade tick        
        $('#id_completionusegrade').parents(".form-group").css('display','none');
    }

    /* Set default value to Activity Completion: completion tracking*/
    $('#id_completion').val(2);

    /* Set default value to Grade section*/
    $('#id_grade_modgrade_type').val('point');
    $('#id_grade_modgrade_point').val(100);
    $('#id_gradecat').val(1);
    $('#id_gradepass').val(100);

    /* Add goone page: Get all details from lesosn-list API */
    $('.lesson-list-select').on('change', function () {

        var selectedids      = $(this).val();
        $("input[name='lesson_id']" ).val(selectedids);
        
    });
    
});