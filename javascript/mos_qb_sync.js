mosqb = {
	sections:{
		activate: function(section_name) {
			$("div.section").removeClass("selected_section");
			$("#"+section_name).addClass("selected_section");
			mosqb[section_name].init();
		}
	},
	welcome:{
		init: function() {
			
		}
	},
	dashboard:{
		init: function() {
			
		}
	},
	settings:{
		qb: {AccountChildren:{}},
		addSubaccounts: function(parent_select) {
				parent_select.parent().find(".subaccounts").remove();
				var subaccounts_holder = $("<div class='subaccounts'></div>");
				parent_select.parent().append(subaccounts_holder);
				var id = parent_select.val();
				if (mosqb.settings.qb.AccountChildren[id]) {
					var childselect = $("<select />");
					childselect.change(function () {
						addSubaccounts(childselect);
					});
					childselect.append("<option value='0' selected='selected'>None</option>");
					$.each(mosqb.settings.qb.AccountChildren[id],function(i,child_account) {
						childselect.append("<option value='"+child_account.Id+"'>"+child_account.Name+"</option>");
					});
					subaccounts_holder.append(childselect);
				}
			},
		init: function() {
			$.getJSON("./jsonapi/GetIntuitAccounts.json.php")
				.success(function(accounts) {
					$("select.qb_account_list option[value='loading']").remove();
					$.each(accounts,function(i,account) {
						if (account.AccountParentId>0) {
							if (!mosqb.settings.qb.AccountChildren[account.AccountParentId]) {
								mosqb.settings.qb.AccountChildren[account.AccountParentId] = [];
							}
							mosqb.settings.qb.AccountChildren[account.AccountParentId].push(account);
						} else {
							$("select.qb_account_list").append("<option value='"+account.Id+"'>"+account.Name+"</option>");
						}
					});
					$("select.qb_account_list").change(function () {
						addSubaccounts($(this));
					});
				})
				.error(function(error) {
					// ?
				});
		}
	}
};

$(document).ready(function() {
	var active_section = $(".section.selected_section").attr('id');
	mosqb[active_section].init();
});

