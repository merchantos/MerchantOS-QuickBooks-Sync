mosqb = {
	error:function (msg) {
		$("#errors ul").append("<li>" + msg + "</li>");
		$("#errors").show();
	},
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
	objects:{
		init: function() {
			// see if we should redirect back to MOS
			var return_url = mosqb.getParameterByName('return_url');
			var return_on_setup = mosqb.getParameterByName('return_on_setup');
			if (return_url && return_on_setup) {
				window.location = return_url;
			}
			
			mosqb.objects.loadObjects().done(function(data) {
				$('#loading').hide();
				$('#objects').show();
			});
		},
		loadObjects: function(page) {
			var selector = "#objects dl.objects";
			if (!page) page = 1;
			return $.getJSON("./jsonapi/LoadObjects.json.php?page="+page).success(function (data) {
				var parent_dl = $(selector);
				parent_dl.children().remove();
				if (data.objects && data.objects.length > 0) {
					$.each(data.objects,function(i,value) {
						parent_dl.append("<dd>" + value['type'] + " (" + value['id'] + ") <a href='#delete-object' object_id='"+value['id']+"' object_type='"+value['type']+"'>Delete</a></dd>");
					});
				} else {
					parent_dl.append("<dd>No Objects</dd>");
				}
				var next = $("<a href='#next'>Next &raquo;</a>");
				next.click(function () {
					mosqb.objects.loadObjects(page+1);
				});
				var previous = $("<a href='#next'>&laquo; Previous</a>");
				previous.click(function () {
					mosqb.objects.loadObjects(page-1);
				});
				if (data.page > 1) {
					$("#objects dl.objects").append(previous);
					if (data.count == 16) {
						parent_dl.append(" ").append(next);
					}
				} else if (data.count == 16) {
					parent_dl.append(next);
				}
				if (data.error) {
					mosqb.error(data.error);
				}
			});
		},
		deleteObject: function(href) {
			var object_id = $(href).attr("object_id");
			var object_type = $(href).attr("object_type");
			if ($(href).parent().parent().find("dt").length == 1) {
				//      dd       dl       div
				$(href).parent().parent().parent().hide();
			}
			$(href).parent().prev().remove();
			$(href).parent().remove();
			$.getJSON("./jsonapi/DeleteObject.json.php?id="+object_id+"&type="+object_type).success(function (data) {
				if (data.error) {
					mosqb.error(data.error);
				}
			});
		}
	},
	dashboard:{
		init: function() {
			// see if we should redirect back to MOS
			var return_url = mosqb.getParameterByName('return_url');
			var return_on_setup = mosqb.getParameterByName('return_on_setup');
			if (return_url && return_on_setup) {
				window.location = return_url;
			}
			
			$.when(mosqb.dashboard.loadHistory(),mosqb.dashboard.loadAlerts()).done(function(data) {
				$('#loading').hide();
				$('#dashboard').show();
			});
		},
		syncNow: function (date,account_log_id,type) {
			var query = "";
			if (date) {
				query = "?date=" + date;
				if (account_log_id)
				{
					query += "&resync_account_log_id=" + account_log_id;
				}
				if (type)
				{
					query += "&type=" + type;
				}
			} else if (type) {
				query = "?type=" + type;
			}
			return $.getJSON("./jsonapi/SyncNow.json.php" + query).done(function (result) {
				if (result.success) {
					mosqb.dashboard.loadHistory();
					mosqb.dashboard.loadAlerts();
				}
				if (result.error) {
					mosqb.error(result.error);
				}
			});
		},
		loadHistory: function(page) {
			mosqb.dashboard._loadLog({
				selector:"#dashboard dl.history",
				callback:this.loadHistory,
				page:(page?page:1),
				alerts:0
			});
		},
		loadAlerts: function(page) {
			mosqb.dashboard._loadLog({
				selector:"#dashboard dl.alerts",
				callback:this.loadAlerts,
				page:(page?page:1),
				alerts:1
			});
		},
		_loadLog: function(options) {
			var selector = (options && options.selector ? options.selector : "#dashboard dl.alerts");
			var page = (options && options.page ? options.page : 1);
			var alerts = (options && options.alerts ? options.alerts : 0);
			var callback = (options && options.callback ? options.callback : this.loadHistory);
			return $.getJSON("./jsonapi/LoadLog.json.php?page="+page+"&alerts="+alerts).success(function (data) {
				var parent_dl = $(selector);
				parent_dl.children().remove();
				if (data.log && data.log.length > 0) {
					if (alerts==1) {
						parent_dl.parent().show();
					}
					$.each(data.log,function(i,value) {
						var dismiss = "";
						if (alerts==1) {
							dismiss = " <a href='#dismiss-alert' alert_id='" + value['account_log_id'] + "'>Dismiss</a>";
						}
						if (value['success']) {
							parent_dl.append("<dt>" + value['date'] + "</dt><dd>" + value['msg'] + dismiss + "</dd>");
						} else {
							parent_dl.append("<dt>" + value['date'] + "</dt><dd>" + value['msg'] + " <a href='#re-sync-day' rday='"+ value['date'] +"' rtype='" + value['type'] + "' account_log_id='" + value['account_log_id'] + "'>Re-Sync</a>" + dismiss +"</dd>");
						}
					});
				} else if (alerts==0) {
					parent_dl.append("<dt>No Events</dt><dd>Hit 'Sync Now' if you'd like to get started pushing your data</dt>");
				} else {
					parent_dl.parent().hide();
				}
				var next = $("<a href='#next'>Next &raquo;</a>");
				next.click(function () {
					callback(page+1);
				});
				var previous = $("<a href='#next'>&laquo; Previous</a>");
				previous.click(function () {
					callback(page-1);
				});
				if (data.page > 1) {
					$("#dashboard dl.history").append(previous);
					if (data.count == 16) {
						parent_dl.append(" ").append(next);
					}
				} else if (data.count == 16) {
					parent_dl.append(next);
				}
				if (data.error) {
					mosqb.error(data.error);
				}
			});
		},
		dismissAlert: function(href)
		{
			var alert_id = $(href).attr("alert_id");
			if ($(href).parent().parent().find("dt").length == 1) {
				//      dt       dl       div
				$(href).parent().parent().parent().hide();
			}
			$(href).parent().prev().remove();
			$(href).parent().remove();
			$.getJSON("./jsonapi/DismissAlert.json.php?account_log_id="+alert_id).success(function (data) {
				if (data.error) {
					mosqb.error(data.error);
				}
			});
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
			
			/*
			$(".setup_group_toggle input").change(function () {
				var group = $(this).attr('id').slice(11); // remove setup_send_
				if ($(this).is(':checked')) {
					$("#setup_group_"+group).show();
				} else {
					$("#setup_group_"+group).hide();
				}
			});
			*/
			
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
						if (result.error) {
							mosqb.error(result.error);
						}
					});
				return false;
			});
			
			var accounts_promise = $.getJSON("./jsonapi/GetIntuitAccounts.json.php").success(function (data) {
				if (data.error) {
					mosqb.error(data.error);
				}});
			var settings_promise = $.getJSON("./jsonapi/LoadSettings.json.php").success(function (data) {
				if (data.error) {
					mosqb.error(data.error);
				}});
			var shops_promise = $.getJSON("./jsonapi/GetMerchantOSShops.json.php").success(function (data) {
				if (data.error) {
					mosqb.error(data.error);
				}});
			var tax_categories_promise = $.getJSON("./jsonapi/GetMerchantOSTaxCategories.json.php").success(function (data) {
				if (data.error) {
					mosqb.error(data.error);
				}});
			
			$.when(accounts_promise,settings_promise,shops_promise,tax_categories_promise).done(function (accounts_data,settings_data,shops_data,tax_categories_data) {
				// list tax categories for mapping
				$("#setup_group_tax div.setup_category").children().remove();
				$.each(tax_categories_data[0],function(i,tax_cat) {
					$("#setup_group_tax div.setup_category")
						.append('<label for="setup_tax_category_'+tax_cat.taxCategoryID+'">'+tax_cat.name+'</label>')
						.append('<div class="account_select"><select class="qb_account_list" id="setup_tax_category_'+tax_cat.taxCategoryID+'" name="setup_tax['+tax_cat.name+']" default_account="Sales Tax Agency Payable"><option value="loading">Loading...</option></select></div>');
				});
				
				// fill the select lists
				$("select.qb_account_list option").remove();
				$("select.qb_account_list").append("<option value='0' selected='selected' disabled='disabled'>Choose Account</option>");
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
				
				// select default accounts
				$("select.qb_account_list").each(function () {
					var default_value = $(this).find('option:contains("'+$(this).attr("default_account")+'")').val();
					$(this).val(default_value);
				});
				
				// list shops for selection
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
	if (active_section) {
		mosqb[active_section].init();
	}

	$("a[href='#settings']").click(function() {
		mosqb.sections.activate('settings');
	});
	
	$("a[href='#objects']").click(function() {
		mosqb.sections.activate('objects');
	});
	
	var syncing = false;
	
	$("a[href='#syncnow']").click(function () {
		if (syncing) return;
		syncing = true;
		var button = $(this);
		button.html("Syncing...");
		mosqb.dashboard.syncNow().done(function (data) {
			button.html("Sync Now");
			syncing = false;
		});
	});
	
	$(document).on("click","a[href='#re-sync-day']",function () {
		var date = $(this).attr("rday");
		var type = $(this).attr("rtype");
		var account_log_id = $(this).attr("account_log_id");
		var button = $("a[href='#syncnow']");
		button.html("Syncing...");
		mosqb.dashboard.syncNow(date,account_log_id,type).done(function (data) {
			button.html("Sync Now");
			syncing = false;
		});
	});
	
	$(document).on("click","a[href='#dismiss-alert']",function () {
		mosqb.dashboard.dismissAlert(this);
	});
	
	$(document).on("click","a[href='#delete-alert']",function () {
		mosqb.objects.deleteObject(this);
	});
	
	$("#errors button").click(function () {
		$("#errors ul li").remove();
		$("#errors").hide();
	});
	
	$("#signup").submit(function() {
		$.ajax({
			type: "POST",
			url: "jsonapi/SaveAccountCreationDetails.json.php",
			data: $("#signup").serialize(), // serializes the form's elements.
			success: function(data) {
				if (data.success) {
					intuit.ipp.anywhere.directConnectToIntuit();
				}
				if (data.error) {
					mosqb.error(result.error);
				}
			}
		});
		return false;
	});
});

