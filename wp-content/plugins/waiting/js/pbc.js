
/*

----------------------------------------------------------------------------------------------------------------------- 
---------------------------------------Free version-------------------------------------------------------------------- 
----------------------------------------------------------------------------------------------------------------------- 
		
*/		

var PBCountdown = function(){};
var PBCUtils = PBCUtils || {};

(function(a){var b=a.jQuery||a.me||(a.me={}),i=function(e,f,g,h,c,a){f||(f=100);var d=!1,j=!1,i=typeof g==="function",l=function(a,b){d=setTimeout(function(){d=!1;if(h||c)e.apply(a,b),c&&(j=+new Date);i&&g.apply(a,b)},f)},k=function(){if(!d||a){if(!d&&!h&&(!c||+new Date-j>f))e.apply(this,arguments),c&&(j=+new Date);(a||!c)&&clearTimeout(d);l(this,arguments)}};if(b.guid)k.guid=e.guid=e.guid||b.guid++;return k};b.throttle=i;b.debounce=function(a,b,g,h,c){return i(a,b,g,h,c,!0)}})(this);

jQuery(document).ready(function($){
	if(window.isfree) delete PBCUtils.em;
	
	PBCUtils.em = PBCUtils.em || jQuery({});
	
	PBCUtils.baseCSS = function(st, scl, cd){
		var type = st.type, o = {}; st = st.css;
		jQuery(cd).css('font-family', st.unit[3])
			.find('.pbc-unit').css({'width': st.unit[1] * scl, 'margin-right': st.unit[2] * scl, 'border-radius': (st.unit[4] * scl)+'%', 'z-index':1})
				.find('.pbc-unit-content').css({'height': st.content[0] * scl,'background': st.num[1], 'color': st.num[2]})
					.find('.pbc-num').css({'font-size': st.num[0] * scl, 'line-height': (st.content[0] * scl)+'px'})
						.css('background', (type[1] ? st.num[1] : ''))
				.end().end()
				.find('.pbc-label').css({
					'font-size': (st.label[0] * scl), 'background': st.label[1], 'color': st.label[2],
					'display': (parseInt(st.label[4]) ? '' : 'none'),
					'line-height': (st.label[0] * scl*1.25)+'px'
					}).css('margin-'+( parseInt(st.label[5]) ? 'bottom' : 'top' ), st.label[3] * scl);
	};
			
	/*	
		simple slide => tranform-origin: 50 50; translate(X||Y): (0||100||-100);
		fall => tranform-origin: 50 50; translate(X:Y): (100||-100) background z:10;

		0-1-0 slide  => tranform-origin: (0||50||100); translate(X||Y): 0;
		0-1-0 orbit => tranform-origin: 50 50; translate(X||Y): (0||100||-100);
		rotate => tranform-origin: (0||50); translate(X||Y): (0||100);
	*/
		
	PBCUtils.center_ords = [
		[-100, -100], [0, -100], [100, -100],
		[-100, 0],    [0, 0],    [100, 0],
		[-100, 100],  [0, 100],  [100, 100],
	];
		
	PBCUtils.ords = [
		[0, 0],   [50, 0],   [100, 0],
		[0, 50],  [50, 50],  [100, 50],
		[0, 100], [50, 100], [100, 100],
	];
		
	PBCUtils.rotags = {
		'02': [50, 0], '06': [0, 50], '28': [100, 50], '68': [50, 100],
		'20': [50, 0], '60': [0, 50], '82': [100, 50], '86': [50, 100]
	};
		
	PBCUtils.zoomCSS = function( opts ){
		var sel = opts[0];
		opts = opts[1];
		opts.ords.map(function(ord){return parseInt(ord); });
		var t = opts.type,
			tra = {'s':opts.ords[0], 'f':opts.ords[1]},
			os = of = [50, 50],
			ts = tf = [0, 0],
			ag = s = [0, 0],
			f = parseInt(opts.r),
			wh = parseInt(opts.s) || 1;
					
		calcAttr( this );
		return [
			sel+' .pbc-zoom-in{'+ this.xcss( calcss(true, os) ) + this.xcss( calcss(false, s[0], ts, ag[0]) ) +'}',
			sel+' .pbc-zoom-out{'+ this.xcss( calcss(true, of) ) +' z-index:0;}',
			sel+' .pbc-slide .pbc-zoom-in, '+sel+' .pbc-zoom-out{'+ this.xcss( calcss(false, 1, [0, 0], 0) ) +'}',
			sel+' .pbc-slide .pbc-zoom-out{'+ this.xcss( calcss(false, s[1], tf, ag[1]) ) +'}'
		].join('');

		function calcss(t_o, s, t, a){
			return t_o ? 'transform-origin: '+ s[0] +'% '+ s[1] +'%;' :
				'transform:scale('+ s +') translateZ(0) translateX('+ t[0] +'%) translateY('+ t[1] +'%) rotate('+ a +'deg);';
		}
		
		function calcAttr(_this){
			switch(t){
				case 'scale':
					s = [0, (opts.s||0)];
					if(s[1]) tf = _this.center_ords[tra.f];
					os = _this.ords[tra.s], of = _this.ords[tra.f];
				break;
				case 'orbit':
					ts = _this.center_ords[tra.s].map(function(i){return i*wh;}),
					tf = _this.center_ords[tra.f].map(function(i){return i*wh;});
				break;
				case 'rotate':
					s = [1, 1],
					ag = [f*180, -f*180],
					os = of = _this.rotags[tra.s.toString() + tra.f.toString()];
				break;	
				default:
					s = [1, 1],
					ts = 1 ? _this.center_ords[tra.s] : [0, 0], tf = _this.center_ords[tra.f];	
			}	
		}
	};
		
	PBCUtils.xcss = function( st ){
		var vendors = ['-webkit-', '-moz-', '-ms-', '-o-', ''], c = '';
		vendors.forEach(function(v){
			c += (v + st);
		});
		return c;
	};
		
	PBCUtils.d3CSS = function( st ){
		var cd = st[0], rules = {}, rule_text, rw = st[2]/2;
		
		rules.dir = st[1][3] ? 'Y' : 'X';
		rules.degree = rules.dir == 'X' ? 90 : -90;
		rules.c = (rules.dir === 'Y' ? ['horz', 'X'] :['vert', 'Y']);
		
		if(st[1][2] == 'flip'){
			rule_text = [
				cd+' .pbc-flip.pbc-slide .pbc-3d-out{',
				this.xcss('transform: rotate'+ rules.dir +'('+ rules.degree +'deg) translateZ('+ rw +'px);'),
				'z-index:20;}'
			];
		} else {
			rules.slide_degree = rules.degree * -1;
			rule_text = [
				cd+' .pbc-cube.pbc-horz .pbc-unit-content, '+cd+' .pbc-cube.pbc-vert .pbc-unit-content{',
					this.xcss('transform: translateZ(-'+ rw +'px);'), '}',
				cd+' .pbc-cube .pbc-3d-out{',
					this.xcss('transform: translateZ('+ rw +'px);'), '}',
				cd+' .pbc-cube .pbc-3d-in{',
					this.xcss('transform: rotate'+ rules.dir +'('+ rules.degree +'deg) translateZ('+ rw +'px);'), '}',
				cd+' .pbc-cube.pbc-slide.pbc-'+ rules.c[0] +' .pbc-unit-content{',
					this.xcss('transition: transform 500ms ease-in-out;'),
					this.xcss('transform: rotate'+ rules.dir +'('+ rules.slide_degree +'deg) translate'+ rules.c[1] +'('+ rw +'px);'),
				'}',
			];	
		}
		return rule_text.join('');
	};
	
	PBCUtils.labelText = function( unit, style ){
		
		var label_text = PBCUtils.lang.units[unit];
		
		if( parseInt(style.shorten_label) ){
			label_text = ( PBCUtils.lang.units[unit].length < 6 ? PBCUtils.lang.units[unit] : PBCUtils.lang.units[unit].substr(0, 3) );
		}
		
		if( parseInt(style.lowercase_label) ){
			label_text = label_text.toLowerCase();
		}
		
		return label_text;
	};
		
	PBCUtils.unitTemplate = function( unit, classes, flip, label_top, style){
		var canvas = flip ? '<canvas width="0" height="0" class="pbc-clips"></canvas>' : ''; 
		var label = '<span class="pbc-label">'+ PBCUtils.labelText( unit, style ) +'</span>';
		return [
			'<div class="pbc-unit pbc-'+unit+' '+classes.unit+'">',
				(label_top ? label : ''),
				'<div class="pbc-unit-content">',
					'<span class="pbc-num pbc-'+(flip ? 'curr' : 'prev')+' pbc-'+classes.num+'-'+(flip ? 'in' : 'out')+'"></span>',
						canvas,
					'<span class="pbc-num pbc-'+(flip ? 'prev' : 'curr')+' pbc-'+classes.num+'-'+(flip ? 'out' : 'in')+'"></span>',
				'</div>',
				(label_top ? '' : label),
			'</div>'
		].join('');
	};
	
	PBCUtils.filterCovers = PBCUtils.filterCovers || function(){ $('.pbc-cover').not(':first').remove();};
	PBCUtils.em.on('pbc.defined', PBCUtils.filterCovers);
		
	PBCUtils.insta = function(meta){
		if(parseInt(meta.insta[1])) return this.timeFromCookie(meta);
		return [+new Date, +new Date + meta.to[1]];
	}
		
	PBCUtils.lag = function(meta){
		if(parseInt(meta.insta[0])) return this.insta(meta);
		return meta.to;
	};

	PBCUtils.baseTemplate = function(id, before){
		return [
			'<div class="pbc-shell" id="pbc-shell-'+id+'">',
				'<div class="pbc-down-'+(before ? 'count' : 'text')+'"></div>',
				'<div class="pbc-down-'+(before ? 'text' : 'count')+'"></div>',
			'</div>'
		].join('');
	};
	
	PBCUtils.isIE = function(){
	   var ua = window.navigator.userAgent;

		var msie = ua.indexOf('MSIE ');
		if (msie > 0) {
			// IE 10 or older => return version number
			return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
		}

		var trident = ua.indexOf('Trident/');
		if (trident > 0) {
			// IE 11 => return version number
			var rv = ua.indexOf('rv:');
			return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
		}

		var edge = ua.indexOf('Edge/');
		if (edge > 0) {
		   // IE 12 => return version number
		   return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
		}

		// other browser
		return false;
	};

	PBCUtils.isCanvasSupported = function(){
	  var elem = document.createElement('canvas');
	  return !!(elem.getContext && elem.getContext('2d'));
	};
				
	PBCountdown.prototype.init = function(m, id, host){	
		this.id = id;
		this.host = host;
		this.meta = m.meta;
		this.down_to = m.meta.to[1];
		this.units = m.meta.units;
		this.curr = {};
		this.prev = {};
		this.style = m.style;
		this.em = $({});
		this.downwidth = 0;
		this.temp = {};
		
		this.initEvents(m);
		this.setupShell();
		this.stage();
		this.count();
		
		return this;
	};


	PBCountdown.prototype.pro = function(){};
		
	PBCountdown.prototype.resize = function(){
		var cd = this, css = cd.style.css,
			scl = cd.style.css.unit[0] = 16,
			pw = cd.down.width(),
			units = cd.units.length,
			uw = parseFloat(css.unit[1]),
			umr = parseFloat(css.unit[2]),
			pad = cd.canvas ? (10/scl) : 0;
			
		css.unit[6] = pad;
		css.num[0] = cd.orgfont[0];
		css.label[0] = cd.orgfont[1];
		
		
		var aw = uw+umr+pad;
		
		pw = pw || (aw*units*scl);
		if(!parseInt(this.style.resize)){
			if(aw*units*scl <= pw) return false;
		}
		
		scl = ((pw/units)/aw);
		
		if(uw*scl < 40) scl *= 2;
		if(scl * css.num[0] < 20)css.num[0] *= 1.35;
		if(scl * css.label[0] < 10)css.label[0] *= 1.35;
		
		css.unit[0] = scl;
	};
	
	PBCountdown.prototype.setupShell = function( m ){
		this.shellsel = '#pbc-shell-' + this.id;
		this.selector =  this.shellsel + ' .pbc-down-count';
		this.host.append( PBCUtils.baseTemplate(this.id) );
		this.down = $(this.selector);
		this.shell = $(this.shellsel);
						
		this.shell.css('width', function(){ return $(this).parent().width(); });
		this.orgfont = new Array(this.style.css.num[0], this.style.css.label[0]);
		this.em.trigger('pbc.setupshell');
	};
	
	PBCountdown.prototype.initEvents = function(m){
		if( PBCUtils.isIE() && m.style.type[2] === 'cube' ) m.style.type = ['html', 0, "zoom", {"ords":[1,7],"type":"slide","r":"1"}];
		if( PBCUtils.isIE() && PBCUtils.isIE() < 10 && m.style.type[0] !== 'canvas') m.style.type = ['html', 1, 'horz', 0];
		if(!PBCUtils.isCanvasSupported()) m.style.type = ['html', 1, 'horz', 0];
		
		this.type = m.style.type[0];
		this.canvas = this.type === 'html' ? false : true;
	
		this.style.type[1] = window.parseInt(this.style.type[1]);
		this.style.css.unit[0] = 16; //window.parseInt(this.style.css.unit[0]) || 16;
		if(typeof this.style.type[3] === 'string')this.style.type[3] = window.parseInt(this.style.type[3]);
		if(this.style.css.unit[3] === 'inherit') this.style.css.unit[3] = this.host.css('font-family');
	
		this.pro( m );
		
		this.em.trigger('pbc.init');
		this.classify();
		
		var cd = this;
		this.em.on('pbc.template', function(){
			var flip  = (cd.style.type[2] === 'flip');
			if(cd.type === 'html' || flip){
				cd.units.forEach(function(unit){
					cd.down.append( PBCUtils.unitTemplate(unit, cd.temp.classes, flip, parseInt(cd.style.css.label[5]), cd.style ));
				});	
			}
		});
		
		this.em.on('pbc.change', function(e, offset){
			if(cd.type === 'html')
				cd.singly(offset);
		});
		
		window.PBC ? $(window).off('resize').on('resize', callResizer)
			: $(window).on('resize', $.throttle(callResizer, 150));
			
		function callResizer(){
			resizeActions();
		}	
		
		function resizeActions(){
			console.log('e')
			cd.shell.remove();
			cd.curr = {};
			cd.setupShell();
			cd.stage();
			cd.count();
		}
	};	
	
	PBCountdown.prototype.classify = function(){
		this.temp.classes = {'unit':'', 'num':''};
		
		if(this.type === 'html'){
			if(this.style.type[1]){
				if(this.style.type[2] === 'cube')
					this.temp.classes.unit = 'pbc-3d pbc-cube '+ ( this.style.type[3] ? 'pbc-horz' : 'pbc-vert');
				if(this.style.type[2] === 'flip')
					this.temp.classes.unit = 'pbc-3d pbc-flip';
				this.temp.classes.num = '3d';
			} else {
				this.temp.classes.unit = 'pbc-2d';
				this.temp.classes.num = this.style.type[2];	
			}
		}
	};

	PBCountdown.prototype.stage = function(){
		var cd = this;
		cd.units.forEach(function(unit){
			cd.prev[unit] = 0;
		});
		this.resize();		
		this.em.trigger('pbc.template');
		cd.applyStyle();
	};

	PBCountdown.prototype.applyStyle = function(){
		$('#pbc-dynamic-style-'+this.id).remove();
		var scl = this.style.css.unit[0], style = '';
		if(parseInt(this.style.css.unit[5])) style = '.pbc-unit:not(:last-child)[data-value="00"]{display:none !important;}';
		
		if(this.canvas){
			jQuery(this.selector)
				.find('.pbc-canvas-unit').css({'margin-right': this.style.css.unit[2] * scl, 'border-radius': (this.style.css.unit[4] * 16)+'%'});
		} else {	
			PBCUtils.baseCSS(this.style, scl, this.selector);
			
			if(this.style.type[1])
				style += PBCUtils.d3CSS(  [this.selector, this.style.type, this.style.css.unit[1] * scl]  );
			else
				style += PBCUtils.zoomCSS( [this.selector, this.style.type[3]] );
		}
		
		style += (this.shellsel + '{ text-align : '+ (this.style.css.unit[7] || 'left') +' }');
		style = '<style id="pbc-dynamic-style-'+this.id+'">'+ style + '</style>';
		$('head').append(style);
		this.em.trigger('pbc.style');
	};
		
	PBCountdown.prototype.diff = function( curr ){
		var cd = this, diff = [];
		cd.prev = cd.curr;
		cd.curr = {};
				
		cd.units.forEach(function(unit){
			cd.curr[unit] = curr[unit];
		});
		
		if(cd.units.indexOf('months') > -1) hasMonths();
		if(cd.units.indexOf('years') > -1) hasYears();
		
		if(cd.onlydays) cd.curr.days = curr.totalDays;
		
		cd.units.forEach(function(unit){
			if(cd.prev[unit] !== cd.curr[unit])
				diff.push(unit);
		});
		
		return diff;
		
		function hasMonths(){
			var weeks = curr.weeks % 4;
			var months = (curr.weeks - weeks) / 4.34812;
			cd.curr.months = Math.round(months);
			if(!cd.curr.weeks) cd.curr.days += (weeks*7);
			cd.curr.weeks = weeks;
		}
		
		function hasYears(){
			var months = cd.curr.months % 12;
			var yrs = (cd.curr.months - months) / 12;
			cd.curr.years = yrs;
			cd.curr.months = months;
		}
	};

	PBCountdown.prototype.count = function(){
		if(this.units.indexOf('years') < 0 && this.units.indexOf('months') < 0
			&& this.units.indexOf('weeks') < 0) this.onlydays = true;
	
		var cd = this, changed_units;
			
		function badal(ev){
			changed_units = cd.diff(ev.offset);
			if(changed_units.length){
				cd.em.trigger('pbc.change', [cd.scale ? ev.offset : changed_units]);
			}	
		}	
		
		function startItPBC(){
			cd.down.wtcountdown(cd.down_to)
				.on('update.countdown', badal)
				.on('finish.countdown', function(ev){
					badal(ev);
					cd.theEnd();
				});
		}
		
		var pbcstarttimer = window.setInterval(function(){
			if(cd.down.wtcountdown) { window.clearInterval(pbcstarttimer); startItPBC(); }
		}, 10);
	};
	
	PBCountdown.prototype.theEnd = function(){
		if(window.PBC)return false;
		switch(this.meta.onfinish[0]){
			case 'redirect':
				window.location = this.meta.onfinish[1][0];
				break;
			case 'hide':
				this.shell.addClass('wpb-force-hide');
				$('body').css(this.offer_pad_dir, 0);
				break;
			case 'event':
				$(this.meta.onfinish[1][0]).trigger(this.meta.onfinish[1][1]);
				break;
			default:
				this.em.trigger('pbc.finish');
		}
	};

	PBCountdown.prototype.singly = function( changed_units ){
		var cd = this;
		changed_units.forEach(function(unit){
		
			var unitel = cd.down.find('.pbc-'+ unit);
			
			unitel.find('.pbc-prev').text( prepZero(cd.prev[unit]) );
			unitel.find('.pbc-curr').text( prepZero(cd.curr[unit]) );
			unitel.removeClass('pbc-slide');
			unitel.attr('data-value', prepZero(cd.curr[unit]) );
			
			window.setTimeout(function(){
				unitel.addClass('pbc-slide');
			}, 35);
		});
		
		function prepZero(nu){
			return nu < 10 ? ('0'+nu) : nu;
		}
	};
	
	PBCUtils.em.trigger('pbc.defined');
	
	PBCUtils.lang = PBCUtils.lang || pbc_translated_terms;
		
	PBCUtils.counter = 0;
	PBCUtils.toUTC = function(date) { return new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(), date.getUTCHours(), date.getUTCMinutes(), date.getUTCSeconds()); }
	
	
	PBCUtils.run = function( t ){
		if(!t.length || t.attr('data-pbc-setup')) return;
		var down = t.data('countdown');
		t.attr('data-pbc-setup', 1);
		down.meta.to = PBCUtils.lag( down.meta );
		new PBCountdown().init(down, PBCUtils.counter++, t);
	};
	
	PBCUtils.run( $('.pbc-cover').eq(0) );
	
	PBCUtils.em.trigger('pbc.run');
	
});
