const BASE_URL = FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;
const CONTROLLER_URL = BASE_URL + "/"+CALLED_PATH;

$(document).ready(function() {

	/*$('#bpkExport').click(function()
	{
		var exportData = $('#exportDate').val();
		var test = $('#test').val();
		if (exportData === '')
		{
			FHC_DialogLib.alertWarning('Bitte alle Felder ausfüllen!');
			return false;
		}

		window.location = (CONTROLLER_URL + '/xmlExport?exportDate='+encodeURIComponent(exportData)+'&test='+encodeURIComponent(test));
	});*/

	/*$('#csvExport').click(function()
	{
		if ($('#exportDate').val() === '')
		{
			FHC_DialogLib.alertWarning('Bitte alle Felder ausfüllen!');
			return false;
		}
		window.open(CONTROLLER_URL + '/csvExport?csvExportDate=' + $('#csvExportDate').val());
	});*/

	/*$('#importCSV').submit(function(e)
	{
		e.preventDefault();

		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + "/csvImport",
			{
				csvFile: this.uploadfile.files
			},
			{
				successCallback: function (data, textStatus, jqXHR)
				{
					if (FHC_AjaxClient.isError(data))
					{
						FHC_DialogLib.alertWarning(FHC_AjaxClient.getError(data));
					}

					if (FHC_AjaxClient.hasData(data))
					{
						FHC_DialogLib.alertSuccess(FHC_AjaxClient.getData(data))
					}
				},
				errorCallback: function (jqXHR, textStatus, errorThrown)
				{
					FHC_DialogLib.alertError(FHC_PhrasesLib.t("ui", "systemfehler"));
				}
			}
		);
	});*/

	$("#datasetActionsTop").append(
		"<div class='row'>"+
			"<div class='col-xs-12 text-center'><i class='fa fa-circle text-danger'></i> Keine bPK gefunden </div>"+
		"</div>"
	);
});

