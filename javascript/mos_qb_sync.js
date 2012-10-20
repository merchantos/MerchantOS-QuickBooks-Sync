mosqb = {
	sections:{
		activate: function(section_name) {
			$("section").removeClass("selected").hide();
			
			$('#loading').show();
			$("#"+section_name).addClass("selected");
			mosqb[section_name].init();
		}
	},
	welcome:{
		init: function() {
			$('#loading').hide();
			$('#welcome').show();
		}
	},
	dashboard:{
		init: function() {
			$('#loading').hide();
			$('#dashboard').show();
		}
	},
	settings:{
		qb: {AccountChildren:{},AccountParents:{}},
		settings: {},
		addSubaccounts: function(parent_select) {
				var depth = parent_select.attr("subaccount_depth");
				if (!depth) { depth = 0; }
				depth = Number(depth);
				parent_select.parent().find(".qb_subaccount_list").each(function () {
					var test_depth = Number($(this).attr('subaccount_depth'));
					if (test_depth!==NaN && test_depth>depth) {
						$(this).remove();
					}
				});

				var id = parent_select.val();
				if (mosqb.settings.qb.AccountChildren[id]) {
					var parent_name = parent_select.attr('name');
					var childselect = $("<select name='"+parent_name+"_child' class='qb_account_list qb_subaccount_list' subaccount_depth='"+(depth+1)+"' />");
					childselect.append("<option value='0' selected='selected'>None</option>");
					$.each(mosqb.settings.qb.AccountChildren[id],function(i,child_account) {
						childselect.append("<option value='"+child_account.Id+"'>"+child_account.Name+"</option>");
					});
					childselect.change(function () {
						mosqb.settings.addSubaccounts($(this));
					});
					parent_select.after(childselect);
				}
			},
		init: function() {
			$("select.qb_account_list").change(function () {
				mosqb.settings.addSubaccounts($(this));
			});
			
			$(".setup_group_toggle input").change(function () {
				var group = $(this).attr('id').slice(11); // remove setup_send_
				if ($(this).is(':checked')) {
					$("#setup_group_"+group).show();
				} else {
					$("#setup_group_"+group).hide();
				}
			});
			
			$("#settings_form").submit(function () {
				var form_data = $(this).serializeArray();
				// add checkboxes that are not checked
				$("#settings_form input[type='checkbox']:not(:checked)").each(function () {
					form_data.push({name:$(this).attr('name'),value:"off"});
				});
				$.ajax({
						type: "POST",
						url: "jsonapi/SaveSettings.json.php",
						data: form_data
					})
					.done(function( result ) {
						if (result.success) {
							mosqb.sections.activate('dashboard');
						}
					});
				return false;
			});
			
			var accounts_promise = $.getJSON("./jsonapi/GetIntuitAccounts.json.php");
			var settings_promise = $.getJSON("./jsonapi/LoadSettings.json.php");
			var shops_promise = $.getJSON("./jsonapi/GetMerchantOSShops.json.php");
			
			$.when(accounts_promise,settings_promise,shops_promise).done(function (accounts_data,settings_data,shops_data) {
				$("select.qb_account_list option").remove();
				$.each(accounts_data[0],function(i,account) {
					if (account.AccountParentId>0) {
						if (!mosqb.settings.qb.AccountChildren[account.AccountParentId]) {
							mosqb.settings.qb.AccountChildren[account.AccountParentId] = [];
						}
						mosqb.settings.qb.AccountChildren[account.AccountParentId].push(account);
						mosqb.settings.qb.AccountParents[account.Id]=account.AccountParentId;
					} else {
						$("select.qb_account_list").append("<option value='"+account.Id+"'>"+account.Name+"</option>");
					}
				});
				
				$("#shop_locations ol").children().remove();
				$.each(shops_data[0],function(i,shop) {
					$("#shop_locations ol").append('<li><label><input type="checkbox" id="setup_shop_'+shop.shopID+'" name="setup_shops['+shop.shopID+']" class="setup_field" checked="checked"> '+shop.name+'</label></li>');
				});
				
				$.each(settings_data[0],function(name,value) {
					// see if we have a child of this, if so then skip it will get set from the child
					if (settings_data[0][name+"_child"]) {
						return true; // break
					}
					
					if (name.slice(-6) == '_child') {
						var parentId = mosqb.settings.qb.AccountParents[value];
						var parents = [];
						while (parentId) {
							name = name.slice(0,-6);
							parents.push(parentId);
							if (mosqb.settings.qb.AccountParents[parentId]) {
								parentId = mosqb.settings.qb.AccountParents[parentId];
								continue;
							}
							parentId = false;
							break;
						}
						parentId = parents.pop();
						while (parentId) {
							var select = $('select.qb_account_list[name="' + name + '"]');
							select.val(parentId);
							mosqb.settings.addSubaccounts(select);
							name = name+"_child";
							parentId = false;
							if (parents.length > 0) {
								parentId = parents.pop();
							}
						}
					}
					if (name === "setup_shops") {
						$.each(value,function(shopID,value) {
							if (value=="on" || value=="On" || value===true) {
								$('#setup_shop_'+shopID).prop('checked',true);
							} else {
								$('#setup_shop_'+shopID).prop('checked',false);
							}
						});
						return true; // continue
					}
					var field = $('#settings_form [name="'+name+'"]');
					if (field.is(":checkbox")) {
						if (value=="on" || value=="On" || value===true) {
							field.prop('checked', true);
						} else {
							field.prop('checked', false);
						}
					} else {
						field.val(value);
					}
					field.change();
					return true; // continue
				});
				
				// see if we should redirect back to MOS
				var return_url = mosqb.getParameterByName('return_url');
				var return_on_setup = mosqb.getParameterByName('return_on_setup');
				if (return_url && return_on_setup) {
					window.location = return_url;
				}
				
				$('#loading').hide();
				$('#settings').show();
			});
		}
	},
	getParameterByName: function(name) {
		name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
		var regexS = "[\\?&]" + name + "=([^&#]*)";
		var regex = new RegExp(regexS);
		var results = regex.exec(window.location.search);
		if(results == null)
			return null;
		else
			return decodeURIComponent(results[1].replace(/\+/g, " "));
	}
};

$(document).ready(function() {
	var active_section = $("section.selected").attr('id');
	mosqb[active_section].init();

	$("a[href='#settings']").click(function() {
		mosqb.sections.activate('settings');
	});
});

