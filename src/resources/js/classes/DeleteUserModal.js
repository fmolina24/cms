(function($) {


Craft.DeleteUserModal = Garnish.Modal.extend(
{
	id: null,
	userId: null,

	$deleteActionRadios: null,
	$deleteSpinner: null,

	currentPasswordModal: null,
	userSelect: null,
	_deleting: false,

	init: function(userId, settings)
	{
		this.id = Math.floor(Math.random()*1000000000);
		this.userId = userId;
		settings = $.extend(Craft.DeleteUserModal.defaults, settings);

		var $form = $(
				'<form class="modal fitted deleteusermodal" method="post" accept-charset="UTF-8">' +
					Craft.getCsrfInput() +
					'<input type="hidden" name="action" value="users/deleteUser"/>' +
					(!Garnish.isArray(this.userId) ? '<input type="hidden" name="userId" value="'+this.userId+'"/>' : '') +
					'<input type="hidden" name="redirect" value="'+(Craft.edition == Craft.Pro ? 'users' : 'dashboard')+'"/>' +
				'</form>'
			).appendTo(Garnish.$bod),
			$body = $(
				'<div class="body">' +
					'<p>'+Craft.t('What do you want to do with the their content?')+'</p>' +
					'<div class="options">' +
						'<label><input type="radio" name="contentAction" value="transfer"/> '+Craft.t('Transfer it to:')+'</label>' +
						'<div id="transferselect'+this.id+'" class="elementselect">' +
							'<div class="elements"></div>' +
							'<div class="btn add icon dashed">'+Craft.t('Choose a user')+'</div>' +
						'</div>' +
					'</div>' +
					'<div>' +
						'<label><input type="radio" name="contentAction" value="delete"/> '+Craft.t('Delete it')+'</label>' +
					'</div>' +
				'</div>'
			).appendTo($form),
			$buttons = $('<div class="buttons right"/>').appendTo($body),
			$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons);

		this.$deleteActionRadios = $body.find('input[type=radio]');
		this.$deleteSubmitBtn = $('<input type="submit" class="btn submit disabled" value="'+(Garnish.isArray(this.userId) ? Craft.t('Delete users') : Craft.t('Delete user'))+'" />').appendTo($buttons);
		this.$deleteSpinner = $('<div class="spinner hidden"/>').appendTo($buttons);

		if (Garnish.isArray(this.userId))
		{
			var idParam = ['and'];

			for (var i = 0; i < this.userId.length; i++)
			{
				idParam.push('not '+this.userId[i]);
			}
		}
		else
		{
			var idParam = 'not '+this.userId;
		}

		this.userSelect = new Craft.BaseElementSelectInput({
			id: 'transferselect'+this.id,
			name: 'transferContentTo',
			elementType: 'User',
			criteria: {
				id: idParam
			},
			limit: 1,
			modalSettings: {
				closeOtherModals: false
			},
			onSelectElements: $.proxy(function()
			{
				if (!this.$deleteActionRadios.first().prop('checked'))
				{
					this.$deleteActionRadios.first().click();
				}
				else
				{
					this.validateDeleteInputs();
				}
			}, this),
			onRemoveElements: $.proxy(this, 'validateDeleteInputs'),
			selectable: false,
			editable: false
		});

		this.addListener($cancelBtn, 'click', 'hide');

		this.addListener(this.$deleteActionRadios, 'change', 'validateDeleteInputs');
		this.addListener($form, 'submit', 'handleSubmit');

		this.base($form, settings);
	},

	validateDeleteInputs: function()
	{
		var validates = false;

		if (this.$deleteActionRadios.eq(0).prop('checked'))
		{
			validates = !!this.userSelect.totalSelected;
		}
		else if (this.$deleteActionRadios.eq(1).prop('checked'))
		{
			validates = true;
		}

		if (validates)
		{
			this.$deleteSubmitBtn.removeClass('disabled')
		}
		else
		{
			this.$deleteSubmitBtn.addClass('disabled')
		}

		return validates;
	},

	handleSubmit: function(ev)
	{
		if (this._deleting || !this.validateDeleteInputs())
		{
			ev.preventDefault();
			return;
		}

		this.$deleteSubmitBtn.addClass('active');
		this.$deleteSpinner.removeClass('hidden');
		this.disable();
		this.userSelect.disable();
		this._deleting = true;

		// Let the onSubmit callback prevent the form from getting submitted
		if (this.settings.onSubmit() === false)
		{
			ev.preventDefault();
		}
	},

	onFadeIn: function()
	{
		// Auto-focus the first radio
		if (!Garnish.isMobileBrowser(true))
		{
			this.$deleteActionRadios.first().focus();
		}

		this.base();
	}
},
{
	defaults: {
		onSubmit: $.noop
	}
});


})(jQuery)