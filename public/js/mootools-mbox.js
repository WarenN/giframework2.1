/*
---
description: mBox.All.min combines all mBox class in one javascript file with minified code

authors: Stephan Wagner

license: MIT-style

requires:
 - core/1.4.5: '*'
 - more/Element.Measure

provides: [mBox]

documentation: http://htmltweaks.com/mBox/Documentation
...
*/
var mBox=new Class({Implements:[Options,Events],options:{id:"",theme:"",addClass:{wrapper:"",container:"",content:"",title:"",footer:""},setStyles:{wrapper:{},container:{},content:{},title:{},footer:{}},target:$(window),attach:null,event:"click",preventDefault:false,width:"auto",height:"auto",zIndex:8e3,content:null,setContent:"data-setContent",load:null,url:"",reload:false,title:null,footer:null,draggable:false,position:{x:"center",y:"center"},fixed:null,offset:{x:0,y:0},pointer:false,fade:{open:true,close:true},fadeDuration:{open:200,close:300},fadeWhenOpen:false,overlay:false,overlayStyles:{color:"black",opacity:.75},overlayFadeDuration:100,transition:{open:null,close:null},closeOnEsc:true,closeOnClick:false,closeOnBoxClick:false,closeOnWrapperClick:false,closeOnBodyClick:true,closeOnMouseleave:false,closeInTitle:false,delayOpen:0,delayClose:0,delayOpenOnce:true,constructOnInit:true,openOnInit:false},initialize:function(e){this.block=false;this.ignoreDelay=false;this.ignoreDelayOnce=false;this.setOptions(e);this.fixOptions();this.getPosition();this.target=this.getTarget();if(this.options.fixed==null){this.options.fixed=[$(window),$(document),$(document.body)].contains(this.target)}if(this.target=="mouse"){this.options.pointer=false}this.targets=[];this.id=this.options.id||"mBox"+ ++mBox.currentId;this.addListeners();if(this.options.constructOnInit){this.construct()}this.fireEvent("init").fireEvent("systemInit");if(this.options.openOnInit){this.open()}mBox.instances.push(this)},reInit:function(){this.addListeners()},fixOptions:function(){if(typeof this.options.addClass=="string"){this.options.addClass={wrapper:this.options.addClass}}if(typeof this.options.setStyles=="object"&&!this.options.setStyles.wrapper&&!this.options.setStyles.container&&!this.options.setStyles.content&&!this.options.setStyles.title&&!this.options.setStyles.footer){this.options.setStyles={wrapper:this.options.setStyles}}this.options.fade={open:this.options.fade.open||this.options.fade==true,close:this.options.fade.close||this.options.fade==true};this.options.fadeDuration={open:this.options.fadeDuration.open||this.options.fadeDuration,close:this.options.fadeDuration.close||this.options.fadeDuration}},construct:function(){if(this.wrapper){return null}this.wrapper=(new Element("div",{id:this.id,"class":"mBox "+(this.defaultTheme||"Core")+(this.options.theme?"-"+this.options.theme:"")+" "+(this.options.addClass.wrapper||""),styles:{zIndex:this.options.zIndex,position:this.options.fixed==false||Browser.ie6||Browser.ie7?"absolute":"fixed",display:"none",opacity:1e-5,top:-12e3,left:-12e3,zoom:1}})).setStyles(this.options.setStyles.wrapper||{}).inject(document.body,"bottom");if(this.options.closeOnMouseleave){this.wrapper.addEvents({mouseenter:function(e){this.open()}.bind(this),mouseleave:function(e){this.close()}.bind(this)})}this.container=(new Element("div",{"class":"mBoxContainer"+" "+(this.options.addClass.container||"")})).setStyles(this.options.setStyles.container||{}).inject(this.wrapper);this.content=(new Element("div",{"class":"mBoxContent"+" "+(this.options.addClass.content||""),styles:{width:this.options.width,height:this.options.height}})).setStyles(this.options.setStyles.content||{}).inject(this.container);this.load(this.options.content,this.options.title,this.options.footer,true);this.fireEvent("systemBoxReady").fireEvent("boxReady")},addListeners:function(e){e=e||this.options.attach;elements=Array.from($(e)).combine(Array.from($$("."+e))).combine(Array.from($$(e))).clean();if(!elements||elements.length==0)return this;this.targets.combine(elements);switch(this.options.event){case"mouseenter":case"mouseover":var t={mouseenter:function(e){this.target=this.getTargetFromEvent(e);this.source=this.getTargetElementFromEvent(e);this.open()}.bind(this),mouseleave:function(e){this.close()}.bind(this)};break;default:var t={click:function(e){if(this.options.preventDefault){e.preventDefault()}if(this.isOpen){this.close()}else{this.target=this.getTargetFromEvent(e);this.source=this.getTargetElementFromEvent(e);this.open()}}.bind(this)}}$$(elements).each(function(e){if(!e.retrieve("mBoxElementEventsAdded"+this.id)){e.addEvents(t).store("mBoxElementEventsAdded"+this.id,true)}}.bind(this))},loadAjax:function(e){if(!this.ajaxRequest){this.ajaxRequest=(new Request.HTML({link:"cancel",update:this.content,onRequest:function(){this.setContent("");this.wrapper.addClass("mBoxLoading")}.bind(this),onComplete:function(){this.wrapper.removeClass("mBoxLoading");if(this.options.width=="auto"||this.options.height=="auto"){this.setPosition()}this.fireEvent("ajaxComplete")}.bind(this)})).send()}this.ajaxRequest.send(e);this.ajaxLoaded=true},open:function(e){if(!this.wrapper){this.construct()}if(typeof e!="object")e={};clearTimeout(this.timer);if(!this.isOpen&&!this.block){var t=function(){this.ignoreDelayOnce=false;this.fireEvent("systemOpenComplete").fireEvent("openComplete")}.bind(this);var n=function(t){this.isOpen=true;if(this.options.load=="ajax"&&this.options.url&&(!this.ajaxLoaded||this.options.reload)){this.loadAjax({url:this.options.url})}this.target=this.getTarget(e.target||null);if(this.options.setContent&&this.source&&this.source.getAttribute(this.options.setContent)){if($(this.source.getAttribute(this.options.setContent))){this.content.getChildren().setStyle("display","none");$(this.source.getAttribute(this.options.setContent)).setStyle("display","")}else{var n=this.source.getAttribute(this.options.setContent).split("|"),r=n[0]||null,i=n[1]||null,s=n[2]||null;this.load(r,i,s)}}this.setPosition(null,e.position||null,e.offset||null);this.fireEvent("systemOpen").fireEvent("open");if(this.fx){this.fx.cancel()}this.wrapper.setStyles({display:""});if(this.options.fadeWhenOpen){this.wrapper.setStyle("opacity",0)}this.fx=(new Fx.Tween(this.wrapper,{property:"opacity",duration:this.options.fadeDuration.open,link:"cancel",onComplete:t}))[e.instant||!this.options.fade.open?"set":"start"](1);if(e.instant||!this.options.fade.open){t()}var o=this.getTransition();if(o.open){var u=new Fx.Tween(this.wrapper,{property:o.open.property||"top",duration:o.open.duration||this.options.fadeDuration.open,transition:o.open.transition||null,onStart:o.open.onStart||null,onComplete:o.open.onComplete||null});u.start(o.open.start||this.wrapper.getStyle(o.open.property||"top").toInt()+(o.open.difference_start||0),o.open.end||this.wrapper.getStyle(o.open.property||"top").toInt()+(o.open.difference_end||0))}this.attachEvents();if(this.options.overlay){this.addOverlay(e.instant||!this.options.fade.open)}if(this.options.delayOpenOnce){this.delayOpenOnce=true}}.bind(this);if(this.options.delayOpen>0&&!this.ignoreDelay&&!this.ignoreDelayOnce&&!this.delayOpenOnce){this.timer=n.delay(this.options.delayOpen,this,t)}else{n(t)}}return this},close:function(e){if(typeof e!="object")e={};clearTimeout(this.timer);if(this.isOpen&&!this.block){var t=function(){this.delayOpenOnce=false;this.ignoreDelayOnce=false;this.wrapper.setStyle("display","none");this.fireEvent("systemCloseComplete").fireEvent("closeComplete")}.bind(this);var n=function(t){this.isOpen=false;this.fireEvent("systemClose").fireEvent("close");this.detachEvents();if(this.options.overlay){this.removeOverlay(e.instant||!this.options.fade.close)}if(this.fx){this.fx.cancel()}this.fx=(new Fx.Tween(this.wrapper,{property:"opacity",duration:this.options.fadeDuration.close,link:"cancel",onComplete:t}))[e.instant||!this.options.fade.close?"set":"start"](0);if(e.instant||!this.options.fade.close){t()}var n=this.getTransition();if(n.close){var r=new Fx.Tween(this.wrapper,{property:n.close.property||"top",duration:n.close.duration||this.options.fadeDuration.close,transition:n.close.transition||null,onStart:n.open.onStart||null,onComplete:n.open.onComplete||null});r.start(n.close.start||this.wrapper.getStyle(n.close.property||"top").toInt()+(n.close.difference_start||0),n.close.end||this.wrapper.getStyle(n.close.property||"top").toInt()+(n.close.difference_end||0))}}.bind(this);if(this.options.delayClose>0&&!this.ignoreDelay&&!this.ignoreDelayOnce){this.timer=n.delay(this.options.delayClose,this,t)}else{n(t)}}return this},addOverlay:function(e){if(!this.overlay){this.overlay=(new Element("div",{styles:{position:"fixed",top:0,left:0,width:"100%",height:"100%",zIndex:this.wrapper.getStyle("zIndex")-1,background:this.options.overlayStyles.color||"white",opacity:.001,display:"none"}})).set("tween",{duration:this.options.overlayFadeDuration,link:"cancel"}).inject($(document.body),"bottom")}this.overlay.setStyle("display","block")[e?"set":"tween"]("opacity",this.options.overlayStyles.opacity||.001);return this},removeOverlay:function(e){if(this.overlay){this.overlay[e?"set":"tween"]("opacity",0).get("tween").chain(function(){this.overlay.setStyle("display","none")}.bind(this))}return this},getTarget:function(e){var e=$(e)||e||this.target||$(this.options.target)||this.options.target||$(this.options.attach);return e=="mouse"?"mouse":this.fixOperaPositioning($(e))},getTargetFromEvent:function(e){if(this.options.target)return this.fixOperaPositioning($(this.options.target));return this.getTargetElementFromEvent(e)},getTargetElementFromEvent:function(e){if(e&&e.target){if(this.targets.contains(e.target))return this.fixOperaPositioning(e.target);var t=e.target.getParent();while(t!=null){if(this.targets.contains(t)){return this.fixOperaPositioning(t)}t=t.getParent()}}return null},fixOperaPositioning:function(e){if($(e)&&!$(e).retrieve("OperaBugFixed")&&e!=window){try{if(!($(e).getStyle("border-top-width").toInt()+$(e).getStyle("border-right-width").toInt()+$(e).getStyle("border-bottom-width").toInt()+$(e).getStyle("border-left-width").toInt())){$(e).setStyle("border",0)}}catch(t){}$(e).store("OperaBugFixed")}return e},getPosition:function(e){if(!e&&this.position)return this.position;e=e||this.options.position;this.position={};this.position.x=typeof e=="object"&&typeof e.x=="number"?[e.x.toInt(),null]:typeof e!="object"||!e.x||e.x=="center"||typeof e.x=="object"&&e.x[0]=="center"?["center",null]:["right","left"].contains(e.x)?[e.x,this.defaultInOut||"inside"]:typeof e.x=="object"&&["right","left"].contains(e.x[0])?[e.x[0],["inside","center","outside"].contains(e.x[1])?e.x[1]:this.defaultInOut||"inside"]:["center",null];this.position.xAttribute=this.position.x[3]=="right"||this.position.x[1]=="inside"&&this.position.x[0]=="right"?"right":"left";this.position.y=typeof e=="object"&&typeof e.y=="number"?[e.y.toInt(),null]:typeof e!="object"||!e.y||e.y=="center"||typeof e.y=="object"&&e.y[0]=="center"?["center",null]:["top","bottom"].contains(e.y)?[e.y,this.defaultInOut||"inside"]:typeof e.y=="object"&&["top","bottom"].contains(e.y[0])?[e.y[0],["inside","center","outside"].contains(e.y[1])?e.y[1]:this.defaultInOut||"inside"]:["center",null];this.position.yAttribute=this.position.x[3]=="bottom"||this.position.y[1]=="inside"&&this.position.y[0]=="bottom"?"bottom":"top";return this.position},getOffset:function(e){if(!e&&this.offset)return this.offset;e=e||this.options.offset;this.offset={};this.offset.x=typeof e=="number"?e:!e.x?0:e.x.toInt()>=0||e.x.toInt()<0?e.x.toInt():0;this.offset.y=typeof e=="number"?e:!e.y?0:e.y.toInt()>=0||e.y.toInt()<0?e.y.toInt():0;return this.offset},getPointer:function(e){if(!e&&this.pointer)return this.pointer;e=e||this.options.pointer;if(!e)return false;var t=this.getPosition();this.pointer={};if(t.y[1]=="outside"){this.pointer.position=t.y[0]=="bottom"?"top":"bottom";this.pointer.adjustment=typeof e=="object"&&["center","right","left"].contains(e[0])?e[0]:["center","right","left"].contains(e)?e:"center"}else if(t.x[1]=="outside"){this.pointer.position=t.x[0]=="left"?"right":"left";this.pointer.adjustment=typeof e=="object"&&["center","top","bottom"].contains(e[0])?e[0]:["center","top","bottom"].contains(e)?e:"center"}else{return null}this.pointer.offset=typeof e=="object"&&e[1]&&typeof e[1].toInt()=="number"?e[1].toInt():0;this.pointer.offset=this.pointer.offset<0?this.pointer.offset*-1:this.pointer.offset;this.pointer.offset=this.pointer.adjustment=="right"||this.pointer.adjustment=="bottom"?this.pointer.offset*-1:this.pointer.offset;return this.pointer},getTransition:function(){if(this.transition)return this.transition;if(this.options.transition&&["flyin","flyout","flyinout","flyoutin","bounce","bouncein","bounceout","bounceinout","bouncefly"].contains(this.options.transition)){this.transition={};this.transition.open={property:this.position.yAttribute=="top"||this.position.yAttribute=="bottom"?this.position.yAttribute:this.position.xAttribute,transition:"quad:out",duration:300};this.transition.close=Object.clone(this.transition.open);var e=20*(this.position.yAttribute=="bottom"||this.position.xAttribute=="right"?-1:1);switch(this.options.transition){case"flyin":case"flyout":this.transition.open.difference_start=this.transition.close.difference_end=e*(this.options.transition=="flyin"?-1:1);break;case"flyinout":case"flyoutin":e=e*(this.options.transition=="flyinout"?1:-1);this.transition.open.difference_start=e*-1;this.transition.close.difference_end=e;break;case"bounce":case"bouncefly":case"bouncein":case"bounceout":case"bounceinout":this.transition.open.transition="bounce:out";this.transition.open.duration=450;this.transition.open.difference_start=e*-1;if(this.options.transition=="bounceinout"||this.options.transition=="bounceout"||this.options.transition=="bouncefly"){this.transition.close.difference_end=e*-1}break}}else{this.transition={};this.transition.open=typeof this.options.transition.open!=undefined?this.options.transition.open:this.options.transition;this.transition.close=typeof this.options.transition.close!=undefined?this.options.transition.close:this.options.transition}return this.transition},setPosition:function(e,t,n){e=this.getTarget(e);t=this.getPosition(t);n=this.getOffset(n);pointer=this.getPointer();if(e=="mouse"){o=(this.mouseX||0)+15+n.x;u=(this.mouseY||0)+15+n.y;this.wrapper.setStyles({left:Math.floor(o),top:Math.floor(u)});return this}if(!e||[$(window),$(document),$(document.body)].contains(e)){var r=this.wrapper.getStyle("position")=="fixed"?{x:0,y:0}:$(window).getScroll(),i=$(window).getSize();i.width=i.totalWidth=i.x;i.height=i.totalHeight=i.y;var s={top:r.y,left:r.x,right:r.x+i.width,bottom:r.y+i.height}}else{if(!this.options.fixed!=true){this.wrapper.setStyle("position","absolute")}var i=e.getDimensions({computeSize:true});var s=e.getCoordinates();if(i.totalWidth==0){i.width=i.totalWidth=s.width;i.height=i.totalHeight=s.height}}var o=s.left,u=s.top;var a=this.wrapper.getDimensions({computeSize:true});if(pointer&&!this.pointerElement){this.pointerElement=(new Element("div",{"class":"mBoxPointer "+"mBoxPointer"+pointer.position.capitalize(),styles:{position:"absolute"}})).setStyle(pointer.position,0).inject(this.wrapper,"top");if(Browser.opera){var f=(new Element("div",{"class":"mBox "+(this.defaultTheme||"Core")+(this.options.theme?"-"+this.options.theme:"")})).inject(document.body).grab(this.pointerElement);this.pointerDimensions=this.pointerElement.getDimensions({computeSize:true});this.pointerElement.inject(this.wrapper,"top");f.destroy()}else{this.pointerDimensions=this.pointerElement.getDimensions({computeSize:true})}this.container.setStyle("margin-"+pointer.position,pointer.position=="left"||pointer.position=="right"?this.pointerDimensions.width-this.container.getStyle("border-"+pointer.position).toInt():this.pointerDimensions.height-this.container.getStyle("border-"+pointer.position).toInt())}if(pointer&&this.pointerElement){if(t.x[1]=="outside"&&t.y[1]=="outside"&&pointer.adjustment=="center"){pointer.adjustment=t.x[0]=="left"?"right":"left";switch(t.x[0]){case"left":o+=a.totalWidth-this.pointerDimensions.width/2;break;case"right":o-=this.pointerDimensions.width/2;break}}var l=0,c=0,h=0,p=0;switch(pointer.adjustment){case"center":c=pointer.position=="top"||pointer.position=="bottom"?a.totalWidth/2-this.pointerDimensions.width/2:a.totalHeight/2-this.pointerDimensions.height/2;break;case"left":case"right":switch(t.x[1]){case"inside":h+=this.pointerDimensions.width/2*-1+(t.x[0]=="right"?a.totalWidth:0);break;default:if(t.x[0]=="center"){h+=a.totalWidth/2-this.pointerDimensions.width/2}}o+=h-(pointer.adjustment=="right"?a.totalWidth-this.pointerDimensions.width:0);c=pointer.adjustment=="right"?a.totalWidth-this.pointerDimensions.width:0;break;case"top":case"bottom":switch(t.y[1]){case"inside":p+=this.pointerDimensions.height/2*-1+(t.y[0]=="bottom"?a.totalHeight:0);break;default:if(t.y[0]=="center"){p+=a.totalHeight/2-this.pointerDimensions.height/2}}u+=p-(pointer.adjustment=="bottom"?a.totalHeight-this.pointerDimensions.height:0);c=pointer.adjustment=="bottom"?a.totalHeight-this.pointerDimensions.height:0;break}switch(pointer.position){case"top":case"bottom":o+=pointer.offset*-1;break;case"left":case"right":u+=pointer.offset*-1;break}this.pointerElement.setStyle(pointer.position=="top"||pointer.position=="bottom"?"left":"top",c+pointer.offset)}a=this.wrapper.getDimensions({computeSize:true});switch(t.x[0]){case"center":o+=i.totalWidth/2-a.totalWidth/2;break;case"right":o+=i.totalWidth-(t.x[1]=="inside"?a.totalWidth:t.x[1]=="center"?a.totalWidth/2:0);break;case"left":o-=t.x[1]=="outside"?a.totalWidth:t.x[1]=="center"?a.totalWidth/2:0;break;default:o=t.x}switch(t.y[0]){case"center":u+=i.totalHeight/2-a.totalHeight/2;break;case"bottom":u+=i.totalHeight-(t.y[1]=="inside"?a.totalHeight:t.y[1]=="center"?a.totalHeight/2:0);break;case"top":u-=t.y[1]=="outside"?a.totalHeight:t.y[1]=="center"?a.totalHeight/2:0;break;default:o=t.y}this.wrapper.setStyles({top:null,right:null,bottom:null,left:null});var d=$(window).getSize();if(t.xAttribute=="right"){o=d.x-(o+a.totalWidth)}if(t.yAttribute=="bottom"){u=d.y-(u+a.totalHeight)}o+=n.x;u+=n.y;this.wrapper.setStyle(t.xAttribute,o.floor());this.wrapper.setStyle(t.yAttribute,u.floor());return this},setContent:function(e,t){if(e!=null){if($(e)||$$("."+e).length>0){this[t||"content"].grab($(e)||$$("."+e));if($(e))$(e).setStyle("display","")}else if(e!=null){this[t||"content"].set("html",e)}}return this},setTitle:function(e){if(e!=null&&!this.titleContainer){this.titleContainer=(new Element("div",{"class":"mBoxTitleContainer"})).inject(this.container,"top");this.title=(new Element("div",{"class":"mBoxTitle "+(this.options.addClass.title||""),styles:this.options.setStyles.title||{}})).inject(this.titleContainer);this.wrapper.addClass("hasTitle");if(this.options.draggable&&window["Drag"]!=null){new Drag(this.wrapper,{handle:this.titleContainer});this.titleContainer.addClass("mBoxDraggable")}if(this.options.closeInTitle){(new Element("div",{"class":"mBoxClose",events:{click:function(){this.close()}.bind(this)}})).grab(new Element("div")).inject(this.titleContainer)}}if(e!=null){this.setContent(e,"title")}return this},setFooter:function(e){if(e!=null&&!this.footerContainer){this.footerContainer=(new Element("div",{"class":"mBoxFooterContainer"})).inject(this.container,"bottom");this.footer=(new Element("div",{"class":"mBoxFooter "+(this.options.addClass.footer||""),styles:this.options.setStyles.footer||{}})).inject(this.footerContainer);this.wrapper.addClass("hasFooter")}if(e!=null){this.setContent(e,"footer")}return this},load:function(e,t,n){this.setContent(e);this.setTitle(t);this.setFooter(n);return this},getHTML:function(e,t,n){this.load(e,t,n);return"<div>"+this.wrapper.get("html")+"</div>"},attachEvents:function(){this.escEvent=function(e){if(e.key=="esc"){this.ignoreDelayOnce=true;this.close()}}.bind(this);if(this.options.closeOnEsc){$(window).addEvent("keyup",this.escEvent)}this.resizeEvent=function(e){this.setPosition()}.bind(this);$(window).addEvent("resize",this.resizeEvent);if(this.options.fixed&&(Browser.ie6||Browser.ie7)){$(window).addEvent("scroll",this.resizeEvent)}this.closeOnClickEvent=function(e){if(this.isOpen&&$(this.options.attach)!=e.target&&!$$("."+this.options.attach).contains(e.target)){this.ignoreDelayOnce=true;this.close()}}.bind(this);if(this.options.closeOnClick){$(document).addEvent("mouseup",this.closeOnClickEvent)}this.closeOnBoxClickEvent=function(e){if(this.isOpen&&(this.wrapper==e.target||this.wrapper.contains(e.target))){this.ignoreDelayOnce=true;this.close()}}.bind(this);if(this.options.closeOnBoxClick){$(document).addEvent("mouseup",this.closeOnBoxClickEvent)}this.closeOnWrapperClickEvent=function(e){if(this.isOpen&&this.wrapper==e.target){this.ignoreDelayOnce=true;this.close()}}.bind(this);if(this.options.closeOnWrapperClick){$(document).addEvent("mouseup",this.closeOnWrapperClickEvent)}this.closeOnBodyClickEvent=function(e){if(this.isOpen&&$(this.options.attach)!=e.target&&!$$("."+this.options.attach).contains(e.target)&&e.target!=this.wrapper&&!this.wrapper.contains(e.target)){this.ignoreDelayOnce=true;this.close()}}.bind(this);if(this.options.closeOnBodyClick){$(document).addEvent("mouseup",this.closeOnBodyClickEvent)}this.mouseMoveEvent=function(e){this.mouseX=e.page.x;this.mouseY=e.page.y;this.setPosition("mouse")}.bind(this);if(this.target=="mouse"){$(document).addEvent("mousemove",this.mouseMoveEvent)}},detachEvents:function(){if(this.options.fixed&&(Browser.ie6||Browser.ie7)){$(window).removeEvent("scroll",this.resizeEvent)}$(window).removeEvent("keyup",this.keyEvent);$(window).removeEvent("resize",this.resizeEvent);$(document).removeEvent("mouseup",this.closeOnClickEvent);$(document).removeEvent("mouseup",this.closeOnBoxClickEvent);$(document).removeEvent("mouseup",this.closeOnWrapperClickEvent);$(document).removeEvent("mouseup",this.closeOnBodyClickEvent);$(document).removeEvent("mousemove",this.mouseMoveEvent)},destroy:function(){mBox.instances.erase(this);this.detachEvents();this.wrapper.dispose();delete this.wrapper}});mBox.instances=[];mBox.currentId=0;mBox.reInit=function(){if(mBox.addConfirmEvents){mBox.addConfirmEvents()}mBox.instances.each(function(e){try{e.reInit()}catch(t){}})};
mBox.Notice=new Class({Extends:mBox,options:{type:"Default",position:{x:["left","inside"],y:["bottom","inside"]},offset:{x:30,y:30},fixed:true,move:true,moveDuration:500,delayClose:4e3,fade:true,fadeDuration:{open:250,close:400},target:$(window),zIndex:1e6,closeOnEsc:false,closeOnBoxClick:true,closeOnBodyClick:false,openOnInit:true},initialize:function(e){this.defaultInOut="inside";this.defaultTheme="Notice";e.onSystemBoxReady=function(){this.container.addClass("mBoxNotice"+(this.options.type.capitalize()||"Default"));if(this.options.move&&(this.position.x[1]=="inside"||this.position.x[0]=="center")&&this.position.y[1]=="inside"&&(this.position.y[0]=="top"||this.position.y[0]=="bottom")){var e=this.wrapper.getDimensions({computeSize:true});this.container.setStyle("position","absolute");this.container.setStyle(this.position.y[0]=="top"?"bottom":"top",0);this.wrapper.setStyles({height:0,width:e.totalWidth,overflowY:"hidden"});this.options.transition={open:{transition:"linear",property:"height",duration:this.options.moveDuration,start:0,end:e.totalHeight+this.options.offset.y}};this.options.offset.y=0;this.options.delayClose+=this.options.moveDuration}};e.onSystemOpen=function(){if($(window).retrieve("mBoxNotice")){$(window).retrieve("mBoxNotice").ignoreDelay=true;$(window).retrieve("mBoxNotice").close()}$(window).store("mBoxNotice",this)};e.onSystemOpenComplete=function(){this.close()};e.onSystemCloseComplete=function(){this.destroy()};this.parent(e)}});
mBox.Modal=new Class({Extends:mBox,options:{event:"click",target:$(window),position:{x:["center","inside"],y:["center","inside"]},fixed:true,draggable:true,overlay:true,overlayStyles:{color:"white",opacity:.001},closeInTitle:true,buttons:null},initialize:function(e){this.defaultInOut="inside";this.defaultTheme="Modal";if(e.buttons){e.onSystemBoxReady=function(){this.addButtons(e.buttons)}}this.parent(e)},addButtons:function(e){if(typeof e!="object")return false;this.setFooter("");this.buttonContainer=(new Element("div",{"class":"mBoxButtonContainer"})).inject(this.footerContainer,"top");e.each(function(t,n){(new Element("button",{id:t.id||"",html:"<label>"+(t.value||t.title)+"</label>","class":"mBoxButton "+(t.addClass||"")+" "+(n==0?"mBoxButtonFirst":n==e.length-1?"mBoxButtonLast":""),styles:t.setStyles||{},events:{mouseup:(t.event||this.close).bind(this)}})).inject(this.buttonContainer)}.bind(this));(new Element("div",{styles:{clear:"both"}})).inject(this.footerContainer,"bottom");return this}});
mBox.Modal.Confirm=new Class({Extends:mBox.Modal,options:{addClass:{wrapper:"Confirm"},buttons:[{addClass:"mBoxConfirmButtonCancel"},{addClass:"button_green mBoxConfirmButtonSubmit",event:function(e){this.confirm()}}],confirmAction:function(){},preventDefault:true,constructOnInit:true},initialize:function(e){this.defaultSubmitButton="Yes";this.defaultCancelButton="No";e.onSystemCloseComplete=function(){this.destroy()};e.onSystemBoxReady=function(){this.addButtons(this.options.buttons)};this.parent(e)},confirm:function(){eval(this.options.confirmAction);this.close()}});mBox.addConfirmEvents=function(){$$("*[data-confirm]").each(function(e){if(!e.retrieve("hasConfirm")){var t=e.getAttribute("data-confirm").split("|"),n=e.getAttribute("data-confirm-action")||(e.get("href")?'window.location.href = "'+e.get("href")+'";':"function() {}");e.addEvent("click",function(e){e.preventDefault();if(r){r.close(true)}var r=(new mBox.Modal.Confirm({content:t[0],confirmAction:n,onOpen:function(){if(!this.footerContainer){return;this.setFooter(null)}this.footerContainer.getElement(".mBoxConfirmButtonSubmit").set("html","<label>"+(t[1]||this.defaultSubmitButton)+"</label>");this.footerContainer.getElement(".mBoxConfirmButtonCancel").set("html","<label>"+(t[2]||this.defaultCancelButton)+"</label>")}})).open()});e.store("hasConfirm",true)}})};window.addEvent("domready",function(){mBox.addConfirmEvents()});
mBox.Tooltip=new Class({Extends:mBox,options:{target:null,event:"mouseenter",position:{x:["center"],y:["top","outside"]},pointer:"center",fixed:false,delayOpenOnce:true},initialize:function(e){this.defaultInOut="outside";this.defaultTheme="Tooltip";this.parent(e)}});