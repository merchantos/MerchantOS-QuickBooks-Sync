qb = {};
qb.AccountChildren = {};

function addSubaccounts(parent_select)
{
	parent_select.parent().find(".subaccounts").remove();
	var subaccounts_holder = $("<div class='subaccounts'></div>");
	parent_select.parent().append(subaccounts_holder);
	var id = parent_select.val();
	if (qb.AccountChildren[id]) {
		var childselect = $("<select />");
		childselect.change(function () {
			addSubaccounts(childselect);
		});
		childselect.append("<option value='0' selected='selected'>None</option>");
		$.each(qb.AccountChildren[id],function(i,child_account) {
			childselect.append("<option value='"+child_account.Id+"'>"+child_account.Name+"</option>");
		});
		subaccounts_holder.append(childselect);
	}
}

$(document).ready(function() {
	$.getJSON("./jsonapi/GetIntuitAccounts.json.php")
		.success(function(accounts) {
			$("select.qb_account_list option[value='loading']").remove();
			$.each(accounts,function(i,account) {
				if (account.AccountParentId>0) {
					if (!qb.AccountChildren[account.AccountParentId]) {
						qb.AccountChildren[account.AccountParentId] = [];
					}
					qb.AccountChildren[account.AccountParentId].push(account);
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
});
