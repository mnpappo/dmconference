
/*

----------------------------------------------------------------------------------------------------------------------- 
---------------------------------------Free version-------------------------------------------------------------------- 
----------------------------------------------------------------------------------------------------------------------- 
		
*/		


var PBC = PBC || {'downs':{}, 'down':{}, 'demos':{'demos':{}}};
	PBC.lang = {'aui': pbc_translated_terms.aui};

jQuery(document).ready(function($){

if(window.isfree) delete PBC.em;

PBC.free = true;
PBC.em = PBC.em || $({});
PBC.units = ['years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds'];

PBC.template = function(selector){
	return $(selector).length ? _.template($(selector).html()) : function(){return '';};
};

PBC.ajax = function(data, success, error, type){
	$.ajax({
		url: ajaxurl,
		type: type || 'POST',
		data: data,
		dataType: 'json',
		success: success,
		error: error
	});
};

PBC.merge = function(o, n, ox, ke){
	for(var k in n){
		if(typeof n[k] !== 'object'){
			if(o === undefined) ox[ke] = n;
			else {
				if(o[k] === undefined) o[k] = n[k];
			}
		} else {
			PBC.merge(o[k], n[k], o, k);
		}	
	}
};

PBC.dummy = {
	'meta': {
		'id': 'nw',
		'name': 'Downtown',
		'units': ['days', 'hours', 'minutes', 'seconds'],
		'occurence': [[+new Date, (+new Date + 7*24*60*60*1000)], 0],
		'onfinish':['nothing'],
		'timezone': 'WP',
		'offset': 0,
		'coffset': 0,
		'insta':[0, 0, 7],
		'offer': ''
	},
	
	'style': {
		'type': ['html', 0, 'zoom', {'ords':[1, 7], 'type':'slide', 'r':1}],
		'css': {
			'unit': [0, 6, 0.3, 'inherit', 0, 0],
			'content': [6, 'rgb(168, 168, 168)'],
			'num': [2, 'rgb(168, 168, 168)', '#fff'],
			'label': [1, 'rgb(37, 37, 37)', 'rgb(220, 220, 220)', 0, 1, 0]
		},
		'resize': 0,
		'shorten_label': 0,
		'lowercase_label': 0
	},
	
	'html':{'d':''}
};	

PBC.dummy.meta.to = [+new Date, PBC.dummy.meta.occurence[0][1]];

PBC.clone = function( d ){ return JSON.parse( JSON.stringify( d ) ); };

PBC.demos.downs = [
	['html', 0, 'zoom', {'ords':[1, 7], 'type':'slide', 'r':1}],
	['html', 1, 'cube', 1],
	['html', 0, 'zoom', {'ords':[3, 5], 'type':'slide', 'r':1}],
	['html', 1, 'cube', 0]
];

PBC.demos.draw = function(){	
	this.downs.forEach(function(down, c){
		var m = PBC.clone( PBC.dummy );
		$('#pbc-demos').append('<div class="pbc-demo" id="pbc-demo-'+c+'" data-id="'+c+'"></div>');
		m.name = 'Downtown '+c;
		m.style.type = down;
		if(down[0] === 'canvas') m.style.css.trace = ['#555', '#000', 1, 0];
		PBC.demos.demos[c] = m;
		new PBCountdown().init( m, 'd'+c, $('#pbc-demo-'+c));
	});
};

PBC.down.adminit = function(down, id){
	PBC.down.data = down;
	PBC.down.id = down.meta.id;
	
	pbcFontLink( );
	$('.pbc-front-form, #pbc-downs').addClass('wpb-hidden');
	$('#pbc-back').removeClass('wpb-force-hide');
	$('#pbc-editor').html( PBC.template('#pbc-countdown-tmpl')( {'down': down, 'id':id, 'units': PBC.units, 'oto': down.meta.occurence[0][1] } ))
		.removeClass('wpb-hidden');
			
	$('#pbc-date-picker').datepicker();		
	PBC.em.trigger('pbca.form');
	PBC.down.redraw();
		
	$('.pbc-option[data-name="'+ down.meta.onfinish[0] +'"]').addClass('pbc-option-active');
};

$('body').on('click', '#pbc-edit-style', function(e){
	$('.pbc-choose-style').attr('id', 'pbc-choose-style').text('Change');
	$('#pbc-toggle-style').removeClass('wpb-force-hide');
	editStyle();
});

$('body').on('click', '#pbc-choose-style', function(e){
	PBC.demos.draw();
	$('.pbc-choose-style').addClass('wpb-force-hide');
	$('html,body').animate({'scrollTop': ($('#pbc-demos').offset().top-50)});
});

$('body').on('click', '#pbc-toggle-style', function(e){
	$('#pbc-styles').toggleClass('wpb-hidden');
});

$('body').on('click', '.pbc-demo', function(e){
	var demo = PBC.demos.demos[ $(this).data('id') ];
	PBC.down.data.style.type = demo.style.type;
	if(demo.style.css.trace) PBC.down.data.style.css.trace = _.clone(demo.style.css.trace);
	PBC.down.redraw();	
	editStyle();
});

PBC.down.redraw = function(){
	filterUnits();
	$('.pbc-live').empty();
	$('style#pbc-dynamic-style-'+PBC.down.id).remove();
	PBC.down.down = new PBCountdown().init( PBC.down.data, PBC.down.id, $('.pbc-live') );
	$('#pbc-live').css('height', 'auto');
	$('#pbc-wrapper').css('margin-bottom', $('#pbc-live .pbc-shell').height());
	
	if(PBC.down.data.meta.offer) PBC.down.reoffer();
	else $('#pbc-live').css(PBC.livepos).addClass('pbc-inline-down');
	
	if(PBC.down.down.canvas)$('.pbc-unit-field .pbc-field:lt(2)').addClass('wpb-force-hide');
	else $('.pbc-unit-field .pbc-field:lt(2)').removeClass('wpb-force-hide');
};

function editStyle(){
	$('#pbc-styles').html( PBC.template('#pbc-html-styles-tmpl')( PBC.down.data ) );
	PBC.em.trigger('pbca.editstyle');
	PBC.rangersAndColors();
}

PBC.rangersAndColors = function(){
	$('.wpb-number-input').pbRanger();
	$('.wpb-color').wpColorPicker({'change': function(e){
			var keys = $(this).data('keys').split(', ');
			changeValue(keys, $(this));
		}
	});
	$('.iris-slider').css({'height': 182, 'margin-left':-5});
	return;
	$('.pbc-field label').css('line-height', function(){
		return $(this).height()+'px';
	});
};

function changeValue(keys, el){
	var num = parseInt(keys[3]),
		val = (el[0].type === 'checkbox') ? (el[0].checked ? 1 : 0) : (num ? parseInt(el.val()) : el.val()),
		meta = keys[0].split('-');
					
	if(meta[0] === 'type') PBC.down.data.style.type[3] = val;
	else PBC.down.data.style.css[meta[0]][meta[1]] = num ? (val / 16) : val;
	
	if(keys[2] === 'border-radius') val += '%';
	$('.pbc-live '+keys[1]).css(keys[2], val );
	
	if( (PBC.down.data.style.type[0] === 'canvas' && keys[2] === 'height') ||
		(meta[0] === 'type') || (el[0].type === 'checkbox')
	) PBC.down.redraw();
}

$('body').on('change', '#pbc-styles .pbc-row:not(#pbc-style-toolbar) input, .wpb-fields input', function(e){
	var keys = $(this).data('keys').split(', ');
	changeValue(keys, $(this));
	
	if(keys[1] == '.pbc-unit-content' && keys[2] == 'height')
		$('.pbc-live .pbc-num').css('line-height', parseInt($(this).val()) +'px' );
		
	if(keys[1] == '.pbc-label' && keys[2] == 'font-size')
		$('.pbc-live .pbc-label').css('line-height', (parseInt($(this).val())*1.25) +'px' );
});

$('body').on('change', '#pbc-fonts select', function(e){
	PBC.down.data.style.css.unit[3]  = $(this).val();
	pbcFontLink();
});

$('body').on('change', '.pbc-unit-field input', function(e){
	var index = $(this).parent().parent().index();
	if(!index || (index === 1 && $(this).parents('.pbc-unit-field').find('input').get(0).checked))
		$(this).parents('.pbc-unit-field').find('input').get(1).checked = true;

	PBC.down.data.meta.units = $('.pbc-unit-field input:checked').map(function(i, el){
		return $(el).val();
	}).get();
	
	filterUnits();
	PBC.down.redraw();
});

function filterUnits(){
	if(PBC.down.data.style.type[0] === 'canvas'){
		PBC.down.data.meta.units.forEach(function(u, i){
			if(u === 'months' || u === 'years') PBC.down.data.meta.units.splice(i, 1);
		});
	}
}

$('body').on('click', '.pbc-option-label', function(e){
	var lab = $(this).parent();
	lab.siblings().removeClass('pbc-option-active').
		end().addClass('pbc-option-active');
	var vs = lab.find('input,textarea').map(function(i, el){
		return el.type === 'checkbox' ? (el.checked ? 1 : '') : $(el).val();
	}).get();
	PBC.down.data.meta.onfinish = [lab.data('name'), vs];	
});

$('body').on('blur', '.pbc-option input, .pbc-option textarea', function(e){
	$(this).parents('.pbc-option').children('.pbc-option-label').trigger('click');
});

$('body').on('change', '#cd_to input[name=pbc_to]', changeToMode);

function changeToMode(){
	if(this.value === 'insta'){
		PBC.down.data.meta.insta[0] = 1;
		PBC.down.data.meta.occurence[0] = [+new Date, 50000];
		PBC.down.data.meta.to = [+new Date, +new Date + 50000];
		$('#cd_to div[data-cd=insta]').html( PBC.durationTemplate( PBC.down.data ) );
		$('#cd_to div[data-cd=to]').empty();
	} else {
		PBC.down.data.meta.insta[0] = 0;
		PBC.down.data.meta.occurence[0] = _.clone(PBC.dummy.meta.occurence[0]);
		PBC.down.data.meta.to = [+new Date, PBC.dummy.meta.occurence[0][1]];
		$('#cd_to div[data-cd=insta]').empty();
		$('#cd_to div[data-cd=to]').html( PBC.dateTemplate( PBC.down.data.meta.to[1] ) );
		$('#pbc-date-picker').datepicker();		
	}
	$('#cd_to div[data-cd='+(this.value)+']').removeClass('wpb-force-hide');
	$('#cd_to div[data-cd='+(this.value === 'insta' ? 'to' : 'insta')+']').addClass('wpb-force-hide');
	PBC.down.redraw();
}

function rawValues(inputs){
	var o = {};
	inputs.each(function(){
		o[this.name] = (this.type === 'checkbox') ? (this.checked ? 1 : 0) : $(this).val();
	});
	return o;
}

function prepareForSaving( form ){
	var raw = rawValues( $(form).find('input.wpb-raw') ),
		to = +new Date(raw.to_date + ' '+ raw.to_hours + ':' + raw.to_mins + ':' + raw.to_secs);
								
	PBC.down.data.meta.name = raw.name;
	PBC.down.data.meta.timezone = $('input[name=timezone]:checked').val();
	PBC.down.data.style.resize = raw.resize;
	PBC.down.data.style.shorten_label = raw.shorten_label;
	PBC.down.data.style.lowercase_label = raw.lowercase_label;
	PBC.down.data.style.css.unit[3] = $('#pbc-fonts select').val();
	
	PBC.down.data.meta.insta = [PBC.down.data.meta.insta[0], raw.pbc_insta_session, raw.pbc_insta_session_days];
		
	PBC.down.data.meta.occurence[0] = [
		( PBC.down.data.meta.occurence[0][0] / 1000 ),
		( parseInt(PBC.down.data.meta.insta[0]) ? raw.pbc_insta : (to/1000) )
	];
	PBC.down.data.meta.offset = PBC.down.data.meta.coffset = new Date().getTimezoneOffset();	
	
	delete PBC.down.data.meta.to;
	delete PBC.down.data.style.selector;
	
	filterUnits();
	PBC.em.trigger('pbca.save', raw);
	return PBC.down.id === 'nw';
}

$('body').on('submit', '#pbc-form', function(e){
	e.preventDefault();
	var is_new = prepareForSaving( this );
		
	$('.pbc-form-save .button-primary').text('Saving...');
	PBC.ajax({'action': 'pbc_save_downs', 'pbc_down': PBC.down.data}, function(re){
		if(!re[1]) return false;
		PBC.down.data.meta.occurence[0] = PBC.down.data.meta.occurence[0].map(function(m){return m*1000;});
		PBC.down.data.meta.to = re[0];
		PBC.down.data.meta.to = PBCUtils.lag(PBC.down.data.meta);
		PBC.down.data.meta.id = re[1];
		
		PBC.downs.list({'d':PBC.down.data, 'id':re[1]}, is_new);
	
		PBC.downs[re[1]] = PBC.down.data;
		PBC.cleanForm();
	}, function(er){
		console.log(er);
	});
});

$('body').on('click', '#pbc-delete-form', function(e){
	$(this).text('Deleting...');
	PBC.ajax({'action': 'pbc_delete_down', 'pbc_down': PBC.down.id}, function(re){
		$('.pbc-down[data-down='+PBC.down.id+']').remove();
		delete PBC.downs[PBC.down.id];
		PBC.cleanForm();
	});
});

$('body').on('click', '.pbc-cancel-form', function(e){
	PBC.cleanForm();
});

PBC.cleanForm = function(){
	delete PBC.downs.nw;
	delete PBC.down.data;
	delete PBC.down.down;
	delete PBC.down.id;
	
	$('#pbc-editor, #pbc-live').empty().css('height', 'auto');
	$('#pbc-downs, #pbc-editor, .pbc-front-form').toggleClass('wpb-hidden');
	$('#pbc-demos').empty();
	$('#pbc-back').addClass('wpb-force-hide');
	$('#pbc-wrapper').css('margin', 0);
	$(window).off('resize');
};

PBC.formError = function(code){
	var messages = {
		'invalid': 'Your inputs are invalid, please have a look at them.'
	};
	$('.pbc-form-message').text( messages[code||'invalid'] ).addClass('pbc-form-error');
	return false;
};


PBC.lang.unit_labels = {
	'days': "Days",
	'hours': "Hours",
	'minutes': "Minutes",
	'months': "Months",
	'seconds': "Seconds",
	'weeks': "Weeks",
	'years': "Years"
};


PBC.lang.show = function(){
	$('#pbc-wrapper').append( PBC.template('#pbc-lang-form-tmpl')(PBCUtils.lang) );
};

$('body').on('submit', '#pbc-lang-form', function(e){
	e.preventDefault(); var fo = $(this);
	PBCUtils.lang.units = rawValues( fo.find('input') );
	fo.find('.button').text('Saving....');
	PBC.ajax({'action': 'pbc_save_lang', 'pbc_lang': PBCUtils.lang.units}, function(re){
		fo.find('.button').text('Save');
	});
});


$('body').on('submit', '#pbc-other-settings', function(e){
	e.preventDefault(); var fo = $(this);
	var fields = rawValues( fo.find('input') );
	fo.find('.button').text('Saving....');
	PBC.ajax({'action': 'pbc_save_other_settings', 'pbc_clean_on_uninstall': fields.clean_on_uninstall }, function(re){
		fo.find('.button').text('Save');
	});
});


$('body').on('click', '.pbc-front-form .pbc-front-form-header ', function(e){
	$(this).next().toggleClass('wpb-zero');
});



PBC.downs.list = function(d, is_new){
	d.oto = PBC.date( new Date(d.d.meta.occurence[0][1]), d.d.meta.coffset );
	if(is_new)
		$('#pbc-downs tbody').append( PBC.template('#pbc-down-tmpl')(d) );
	else	
		$('#pbc-downs tbody tr[data-down='+d.id+']').replaceWith( PBC.template('#pbc-down-tmpl')(d) );
};

PBC.downs.createNew = function(){
	PBC.downs['nw'] = JSON.parse( JSON.stringify( PBC.dummy ) );
	PBC.down.adminit( PBC.downs['nw'], 'nw' );
};

PBC.downs.deploy = function( downs ){
	$('#pbc-init-loader').addClass('wpb-hidden');
	PBC.lang.show();
	downs.forEach(function(down){
		PBC.downs[ down.meta.id ] = down;
		down.meta.occurence[0] = down.meta.occurence[0].map(function(m){return m*1000;});
		down.meta.to = PBCUtils.lag( down.meta );
		PBC.downs.list({'d':down, 'id':down.meta.id}, true);
	});
	
	if(downs.length){
		$('#pbc-downs, #pbc-editor').toggleClass('wpb-hidden');
	} else {
		PBC.downs.createNew();
	}
	
	$('body').on('click', 'tr .pbc-edit', function(e){
		PBC.down.adminit( PBC.downs[ $(this).data('down') ], $(this).data('down') );
	});
	
	$('body').on('click', '#pbc-new-countdown', function(e){
		PBC.downs.createNew();
	});
	PBC.em.trigger('pbca.deployed');
};

PBC.dateTemplate = function( oto, offset ){
	return PBC.template('#pbc-countdown-date-tmpl')({'oto': PBC.date( new Date(oto), offset )});
};

PBC.durationTemplate = function( down ){
	return PBC.template('#pbc-duration-tmpl')(down) + PBC.template('#pbc-session-duration-tmpl')(down);
};

PBC.date = function(d, offset){
	offset = offset || 0;
	d = new Date( ((d.getTimezoneOffset() - parseFloat(offset)) * 60 * 1000) + (+d));
	var d_o = {'y': d.getFullYear(), 'mo': (d.getMonth()+1), 'd': d.getDate(), 'h':d.getHours(), 'mi':d.getMinutes(), 's':d.getSeconds()};
		d_o.mo = d_o.mo < 10 ? '0'+d_o.mo : d_o.mo;
		d_o.d = d_o.d < 10 ? '0'+d_o.d : d_o.d;
		d_o.date = d_o.mo + '/' + d_o.d + '/' + d_o.y;
		d_o.time = d_o.h + ':' + d_o.mi + ':' +d_o.s;
	return d_o;	
};

PBC.fonts = {};
PBC.fontTemplate = function( ){
	return PBC.template('#pbc-font-tmpl')({ 'chosen_font': PBC.down.data.style.css.unit[3] });
};

function pbcFontLink(){
	if(PBC.down.data.style.css.unit[3] !== 'inherit')
		$('#pbc-font-link').attr('href', '//fonts.googleapis.com/css?family='+PBC.down.data.style.css.unit[3]);
	$('#pbc-live .pbc-down-count').css('font-family', PBC.down.data.style.css.unit[3]);
}

PBC.downs.init = function(){ 
	PBC.ajax({'action': 'pbc_get_downs'}, function(re){
		PBC.downs.deploy(re);
	}, function(er){
		console.log(er);
	}, 'GET');
	
	PBC.ajax({'action': 'pbc_get_fonts'}, function(re){
		PBC.fonts = re;
		if(PBC.down.down) $('#pbc-fonts').html( PBC.fontTemplate() );
	}, function(er){
		console.log(er);
	}, 'GET');
		
	$('#pbc-wrapper').append(PBC.template('#pbc-main-tmpl'));
	$('body').append('<div id="pbc-live" class="pbc-live pbc-inline-down pbc-live-sticky"></div>');
	PBC.livepos = {'left': $('#pbc-wrapper').offset().left, 'width':$('#pbc-editor').outerWidth()};
	$('#pbc-live.pbc-inline-down').css(PBC.livepos);
	$('head').append('<link rel="stylesheet" type="text/css" href="" id="pbc-font-link">');
};

PBC.em.trigger('pbca.init');
PBC.downs.init();

$('.update-nag').remove();
$.fn.pbRanger = function(){
	this.each(function(){
		if($(this).is('.wpb-ranger')) return;
		var pw = 150 - 30;
		var tr = rangee( $(this).data('range') );
		pw = Math.round($(this).val() / (tr/pw));
		$(this).addClass('wpb-ranger').replaceWith(
			'<div class="wpb-number-slider">'+this.outerHTML+'\
				<span class="wpb-num-slider-wrapper">\
					<span class="wpb-num-slider-bg"></span>\
					<span class="wpb-num-slider" style="left:'+ pw +'px"></span></span></div>');
	});
	
	$('body').on({
		'mousedown': function(e){
			var range = $(this).parent().prev().data('range'),
				th = $(this), thp = th.parent(), fl, rw = thp.width() - 30;
				
			$(document).on('mousemove', function(ev){
				fl = Math.round(ev.pageX - thp.offset().left - 15);
				fl = fl < 1 ? 0 : (fl > (rw-1) ? (rw) : fl);
				if((fl >= 0) && fl <= rw){
					th.css('left', fl);
					thp.prev().val(Math.round( fl / (rw/(range[1] - range[0])) ) + range[0]).trigger('change');
				}
			});
			
			$(document).on('mouseup', function(ev){
				$(document).off('mouseup').off('mousemove');
				thp.prev().trigger('release');
			});	
		}		
	}, '.wpb-num-slider');
	
	function rangee(a){
		return (a[1] - a[0]);
	}
};

});


/*

	=> Resize


*/
