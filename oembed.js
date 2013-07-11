/**
 * __tec_embed is a small framework to provide dynamic styling for embedded events
 * based on saltjs (https://github.com/james2doyle/saltjs)
 * @version 1
 * @since  1
 */
if(!window.__tec_oembed){
	window.__tec_oembed = function(s) {
		var m = {
			'#': 'getElementById',
			'.': 'getElementsByClassName',
			'@': 'getElementsByName',
			'=': 'getElementsByTagName',
			'*': 'querySelectorAll'
		}[s[0]];
		return (document[m](s.slice(1)));
	};
	Element.prototype.find = Element.prototype.querySelectorAll;
	NodeList.prototype.forEach = Array.prototype.forEach;
	Element.prototype.attr = function(n, v) {
		if ( typeof n === 'object' ) {
			for (var k in n){
				this.setAttribute(k, n[k]);
			}
		} else if(v) {
			this.setAttribute(n, v);
		} else {
			return this.getAttribute(n);
		}
	};
	Element.prototype.css = function(p, v) {
		if ( typeof p === 'object' ) {
			for (var k in p){
				this.style[k] = p[k];
			}
		} else if (v) {
			this.style[p] = v;
		} else {
			return this.style[p];
		}
	};

	__tec_oembed('.tribe-event-embed').forEach(function(embed){
		embed.css({
			'background':'#EEE',
			'border-radius':'5px',
			'padding':'3px',
			'margin':'0',
			'position':'relative', 
			'zoom':'1', 
			'display':'block',
			'unicode-bidi':'embed'
		});
		embed.find('a').forEach(function( link ){
			link.attr('target','_blank');
		});
		embed.find('.wrapper')[0].css({
			'position':'relative', 
			'zoom':'1', 
			'display':'block',
			'unicode-bidi':'embed',
			'padding':'0',
			'background':'#fff',
			'border':'1px solid #ccc',
			'font':'normal normal normal 12px/16px "Helvetica Neue",Arial,sans-serif'
		});
		title = embed.find('.title')[0];
		title.css({
			'background-color':'#eaeaea',
			'background-image':'-moz-linear-gradient(#fafafa, #eaeaea)',
			'background-image':'-webkit-linear-gradient(#fafafa, #eaeaea)',
			'background-image':'linear-gradient(#fafafa, #eaeaea)',
			'background-repeat':'repeat-x',
			'border-bottom':'1px solid #CACACA',
			'padding':'10px',
			'margin':'0'
		});
		url = title.find('.url')[0];
		url.css({
			'color':'#555',
			'text-decoration':'none'
		});
		// url.attr('target','_blank');
		title.find('.cost')[0].css({
			'float':'right',
			'font-weight':'normal',
			'color':'#aaa'
		});
		featured = embed.find('.featured')[0];
		if( featured ) {
			featured.css({
				'float':'left',
				'margin':'10px 0 10px 10px'
			});
			
			featured.find('a')[0].css('border','none');
			featured.find('img')[0].css('width','75px');
		}
		meta = embed.find('.meta')[0];
		if( meta ){
			meta.css({
				'float':'left',
				'margin':'10px'
			});
			venue = meta.find('.venue')[0];
			venue.css({
				'font-size':'16px',
				'font-weight':'bold',
				'color':'#555',
				'padding':'5px 0'
			});
			venue.find('a')[0].css({
				'color':'#555',
				'text-decoration':'none'
			});
			venue.find('span a')[0].css({
				'font-size':'12px',
				'font-weight':'normal',
				'text-decoration':'none',
				'color':'#bbb'
			});
			meta.find('.read-more')[0].css({
				'font-size':'12px',
				'font-weight':'normal',
				'text-decoration':'none',
				'color':'#bbb',
				'border':'1px solid #ccc',
				'padding':'5px',
				'background-color':'#eaeaea',
				'background-image':'-moz-linear-gradient(#fafafa, #eaeaea)',
				'background-image':'-webkit-linear-gradient(#fafafa, #eaeaea)',
				'background-image':'linear-gradient(#fafafa, #eaeaea)',
				'background-repeat':'repeat-x',
				'color':'#555',
				'border-radius':'5px'
			});
		}
	});

}