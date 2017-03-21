jQuery(document).ready(function(){
    $otherInput = jQuery("#other_reason");
    $otherRadio = jQuery("#other-page");

    $otherInput.click(function(){
        $otherRadio.prop("checked", true);
    });

    $otherInput.on('input',function(e){
        $inputValue = $otherInput.val();
        $otherRadio.val($inputValue); 
    });
});