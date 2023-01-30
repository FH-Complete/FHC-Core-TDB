const BASE_URL = FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;
const CONTROLLER_URL = BASE_URL + "/"+CALLED_PATH;

$(document).ready(function() {
	var personid = $("#hiddenpersonid").val();


	$('#speichern').click(function()
	{
		var bpkZP = $('#bpkZP').val();
		var bpkAS = $('#bpkAS').val();

		if (bpkZP === '' || bpkAS === '')
			return FHC_DialogLib.alertWarning('Bitte alle Felder ausfüllen!');

		var data = {
			'bpkZP' : bpkZP,
			'bpkAS' : bpkAS,
			'person_id' : personid
		}

		BPKDetails.saveBPKs(data);
	});
});

var BPKDetails = {

	saveBPKs: function(data)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + '/saveBPKs',
			data,
			{
				successCallback: function(data, textStatus, jqXHR) {
					if (FHC_AjaxClient.isSuccess(data))
					{
						FHC_DialogLib.alertSuccess(FHC_AjaxClient.getData(data))

						$('#bpkZP').prop('disabled', true);
						$('#bpkAS').prop('disabled', true);
						$('#speichern').prop('disabled', true);
					}

					if (FHC_AjaxClient.isError(data))
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data))

				},
				errorCallback: function(jqXHR, textStatus, errorThrown) {
					FHC_DialogLib.alertError("Fehler beim Speichern der BPKs");
				}
			}
		);
	}
}