/**
 * AJAX Request, Get Channel Infos
 *
 * @author Oskar Truffer <ot@studer-raimann.ch>
 * @author Fabian Schmid <fs@studer-raimann.ch>
 *
 */
$(document).ready(function () {
	var setChannelLink = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/getChannelInfo.php";
	console.log(setChannelLink);
	var checked = 1;
	var title;
	var discipline;
	var license;
	var estimated_duration;


	$("#channel_type_2").click(function () {
		setChannel();
		checked = 2;
	});

	$("#channel_id").change(function () {
		setChannel();
		checked = 2;
	})


	$("#channel_type_1").click(function () {
		if (checked == 2) {
			unsetChannel();
			checked = 1;
		}
	});

	var setChannel = function () {
		$.ajax({
			url: setChannelLink,
			data: { name: "cmd[getChannelInfoAsJson]", ext_id: $("#channel_id option:selected").val() },
			success: function (data) {
				fillChannel(data);
			}
		});
	}

	var unsetChannel = function () {
		$("input[name='title']").val("").prop("disabled", false);

		//discipline
		$("#discipline_0").prop("disabled", false);

		//licence
		$("#license").prop("disabled", false);

		//lifetime
		$("#lifetime_of_content_in_months").prop("disabled", false);

		//estimated time
		$("#estimated_content_in_hours").val("").prop("disabled", false);

		//intended lifetime
		$("#department").val("").prop("disabled", false);

		//allow annotations
		$("#allow_annotations").prop("checked", false).prop("disabled", false);
	}

	var fillChannel = function (data) {
		try {
//			console.log(data);
//			var json = JSON.parse(data);
			var data = data;

		} catch (e) {

			//Logged out of ILIAS
			//window.location = "/ilias.php";
		}

		//title
		//$("input[name='title']").val(json.title["0"]).prop("disabled", true);
		$("input[name='title']").val(data.title).prop("disabled", false);

		//discipline
		$("#discipline_0").prop("disabled", false);
		$("#discipline_0 option").prop("selected", false)
		$("#discipline_0 option:contains('" + data.discipline + "')").prop("selected", true);
		//$("#discipline_0").prop("disabled", true);
		$("#discipline_0").prop("disabled", false);

		//licence
		console.log(data.license);
		$("#license").prop("disabled", false);
		$("#license option").prop("selected", false);
		$("#license option:contains('" + data.license + "')").prop("selected", true);
		//$("#license").prop("disabled", true);
		$("#license").prop("disabled", false);

		//lifetime
		$("#lifetime_of_content_in_months").prop("disabled", false);
		$("#lifetime_of_content_in_months option").prop("selected", false);
		$("#lifetime_of_content_in_months option:contains('" + data.lifetime + "')").prop("selected", true);
		//$("#lifetime_of_content_in_months").prop("disabled", true);
		$("#lifetime_of_content_in_months").prop("disabled", false);

		//estimated time
		// $("#estimated_content_in_hours").val(json.estimated_duration).prop("disabled", true);
		$("#estimated_content_in_hours").val(data.estimated_duration).prop("disabled", false);

		//intended lifetime
		//$("#department").val(json.department).prop("disabled", true);
		$("#department").val(data.department).prop("disabled", false);

		//allow annotations
		console.log(data.allow_annotations);
		// $("#allow_annotations").prop("checked", json.allow_annotations=="yes").prop("disabled", true);
		$("#allow_annotations").prop("checked", data.allow_annotations == "yes").prop("disabled", false);


	}
});
