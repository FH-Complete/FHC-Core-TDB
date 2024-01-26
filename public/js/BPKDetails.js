const BASE_URL = FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;
const CONTROLLER_URL = BASE_URL + "/"+CALLED_PATH;

$(document).ready(function() {
	var personid = $("#hiddenpersonid").val();


	$('#suchen').click(function()
	{
		var suchParams = {};

		if ($('#vorname').val() === '' || $('#nachname').val() === '')
			return FHC_DialogLib.alertWarning('Bitte Vorname und Nachname ausfüllen!');

		$('.needed').each(function() {
			if ($(this).val() !== '')
			{
				suchParams[$(this).attr('id')] = $(this).val();
			}
		});

		if (Object.keys(suchParams).length < 3)
			return FHC_DialogLib.alertWarning('Bitte mindestens 3 Felder ausfüllen!');

		suchParams['person_id'] = personid;

		BPKDetails.searchBPKs(suchParams);
	});
});

var BPKDetails = {

	searchBPKs: function(data)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + '/searchBPKs',
			data,
			{
				successCallback: function(data, textStatus, jqXHR) {
					if (FHC_AjaxClient.isSuccess(data))
						FHC_DialogLib.alertSuccess(FHC_AjaxClient.getData(data));

					if (FHC_AjaxClient.isError(data))
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data));

				},
				errorCallback: function(jqXHR, textStatus, errorThrown) {
					FHC_DialogLib.alertError("Fehler beim Speichern der BPKs");
				}
			}
		);
	}
}