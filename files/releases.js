

function UpdateFileField() 
{
    var file_count = document.getElementById( 'file_count').value;
    var inner = '';
    var innerDescription = '';
    for( var i=0; i<file_count; i++ ) {
        if ( inner != '' ) {
            inner += '<br />';
            innerDescription += '<br />';
        }
        inner += '<input name="file_' + i + '" type="file" size="40" style="display:inline" />';
        innerDescription += '<input name="description_' + i + '" size="100" maxlength="150"></textarea>'
    }
    document.getElementById( 'FileField' ).innerHTML = inner;
    document.getElementById( 'DescriptionField' ).innerHTML = innerDescription;
}

function ConfirmDelete(event)
{
    mssg = document.getElementById('releases_confirm_delete_file').title;
    if( confirm( mssg ) )
    {
	    return true;
    }
    
    event.preventDefault();
    return false;
}

function ConfirmDeleteVersion(event)
{
    mssg = document.getElementById('releases_confirm_delete_version').title;
    if( confirm( mssg ) )
    {
	    return true;
    }
    
    event.preventDefault();
    return false;
}

UpdateFileField();

document.addEventListener('DOMContentLoaded', function () 
{
  document.getElementById('file_count')
            .addEventListener('change', UpdateFileField );

  var elems = document.getElementsByClassName('releases_delete');
  for( var i=0; i < elems.length; i++ )
  {
    elems[i].addEventListener('click', ConfirmDelete);
  }

  var elems2 = document.getElementsByClassName('version_delete');
  for( var i=0; i < elems2.length; i++ )
  {
    elems2[i].addEventListener('click', ConfirmDeleteVersion);
  }
  
});
            
