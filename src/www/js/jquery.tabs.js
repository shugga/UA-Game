/**
 * Accessible Tabs - jQuery plugin for accessible, unobtrusive tabs 
 * code: http://github.com/ginader/Accessible-Tabs
 * please report issues at: http://github.com/ginader/Accessible-Tabs/issues
 *
 * Copyright (c) 2007 Dirk Ginader (ginader.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * Version: 1.9.4
 */
(function(b){var c=true;b.fn.extend({getUniqueId:function(f,e,d){if(d===undefined){d="";}else{d="-"+d;}return f+e+d;},accessibleTabs:function(f){var h={wrapperClass:"content",currentClass:"current",tabhead:"h4",tabheadClass:"tabhead",tabbody:".tabbody",fx:"show",fxspeed:"normal",currentInfoText:"current tab: ",currentInfoPosition:"prepend",currentInfoClass:"current-info",tabsListClass:"tabs-list",syncheights:false,syncHeightMethodName:"syncHeight",cssClassAvailable:false,saveState:false,autoAnchor:false,pagination:false,position:"top",wrapInnerNavLinks:"",firstNavItemClass:"first",lastNavItemClass:"last"};
var e={37:-1,38:-1,39:+1,40:+1};var d={top:"prepend",bottom:"append"};this.options=b.extend(h,f);var g=0;if(b("body").data("accessibleTabsCount")!==undefined){g=b("body").data("accessibleTabsCount");
}b("body").data("accessibleTabsCount",this.size()+g);var i=this;return this.each(function(w){var k=b(this);var s="";var l=0;var j=[];b(k).wrapInner('<div class="'+i.options.wrapperClass+'"></div>');
b(k).find(i.options.tabhead).each(function(x){var z="";elId=b(this).attr("id");if(elId){if(elId.indexOf("accessibletabscontent")===0){return;}z=' id="'+elId+'"';
}var t=i.getUniqueId("accessibletabscontent",g+w,x);var y=i.getUniqueId("accessibletabsnavigation",g+w,x);j.push(t);if(i.options.cssClassAvailable===true){var m="";
if(b(this).attr("class")){m=b(this).attr("class");m=' class="'+m+'"';}s+='<li id="'+y+'"><a'+z+""+m+' href="#'+t+'">'+b(this).html()+"</a></li>";}else{s+='<li id="'+y+'"><a'+z+' href="#'+t+'">'+b(this).html()+"</a></li>";
}b(this).attr({id:t,"class":i.options.tabheadClass,tabindex:"-1"});l++;});if(i.options.syncheights&&b.fn[i.options.syncHeightMethodName]){b(k).find(i.options.tabbody)[i.options.syncHeightMethodName]();
b(window).resize(function(){b(k).find(i.options.tabbody)[i.options.syncHeightMethodName]();});}var p="."+i.options.tabsListClass;if(!b(k).find(p).length){b(k)[d[i.options.position]]('<ul class="clearfix '+i.options.tabsListClass+" tabamount"+l+'"></ul>');
}b(k).find(p).append(s);var u=b(k).find(i.options.tabbody);if(u.length>0){b(u).hide();b(u[0]).show();}b(k).find("ul."+i.options.tabsListClass+">li:first").addClass(i.options.currentClass).addClass(i.options.firstNavItemClass).find("a")[i.options.currentInfoPosition]('<span class="'+i.options.currentInfoClass+'">'+i.options.currentInfoText+"</span>").parents("ul."+i.options.tabsListClass).children("li:last").addClass(i.options.lastNavItemClass);
if(i.options.wrapInnerNavLinks){b(k).find("ul."+i.options.tabsListClass+">li>a").wrapInner(i.options.wrapInnerNavLinks);}b(k).find("ul."+i.options.tabsListClass+">li>a").each(function(m){b(this).click(function(t){t.preventDefault();
k.trigger("showTab.accessibleTabs",[b(t.target)]);if(i.options.saveState&&b.cookie){b.cookie("accessibletab_"+k.attr("id")+"_active",m);}b(k).find("ul."+i.options.tabsListClass+">li."+i.options.currentClass).removeClass(i.options.currentClass).find("span."+i.options.currentInfoClass).remove();
b(this).blur();b(k).find(i.options.tabbody+":visible").hide();b(k).find(i.options.tabbody).eq(m)[i.options.fx](i.options.fxspeed);b(this)[i.options.currentInfoPosition]('<span class="'+i.options.currentInfoClass+'">'+i.options.currentInfoText+"</span>").parent().addClass(i.options.currentClass);
b(b(this).attr("href"),true).focus().keyup(function(x){if(e[x.keyCode]){i.showAccessibleTab(m+e[x.keyCode]);b(this).unbind("keyup");}});});b(this).focus(function(t){b(document).keyup(function(x){if(e[x.keyCode]){i.showAccessibleTab(m+e[x.keyCode]);
}});});b(this).blur(function(t){b(document).unbind("keyup");});});if(i.options.saveState&&b.cookie){var o=b.cookie("accessibletab_"+k.attr("id")+"_active");
a(b.cookie("accessibletab_"+k.attr("id")+"_active"));if(o!==null){i.showAccessibleTab(o,k.attr("id"));}}if(i.options.autoAnchor&&window.location.hash){var r=b("."+i.options.tabsListClass).find(window.location.hash);
if(r.size()){r.click();}}if(i.options.pagination){var n='<ul class="pagination">';n+='    <li class="previous"><a href="#{previousAnchor}"><span>{previousHeadline}</span></a></li>';
n+='    <li class="next"><a href="#{nextAnchor}"><span>{nextHeadline}</span></a></li>';n+="</ul>";var v=b(k).find(".tabbody");var q=v.size();v.each(function(t){b(this).append(n);
var y=t+1;if(y>=q){y=0;}var z=t-1;if(z<0){z=q-1;}var A=b(this).find(".pagination");var x=A.find(".previous");x.find("span").text(b("#"+j[z]).text());x.find("a").attr("href","#"+j[z]).click(function(B){B.preventDefault();
b(k).find(".tabs-list a").eq(z).click();});var m=A.find(".next");m.find("span").text(b("#"+j[y]).text());m.find("a").attr("href","#"+j[y]).click(function(B){B.preventDefault();
b(k).find(".tabs-list a").eq(y).click();});});}});},showAccessibleTab:function(e,h){a("showAccessibleTab");var g=this;if(h){var f=b("#"+h);var d=f.find("ul."+g.options.tabsListClass+">li>a");
f.trigger("showTab.accessibleTabs",[d.eq(e)]);d.eq(e).click();}else{return this.each(function(){var j=b(this);j.trigger("showTab.accessibleTabs");var i=j.find("ul."+g.options.tabsListClass+">li>a");
j.trigger("showTab.accessibleTabs",[i.eq(e)]);i.eq(e).click();});}},showAccessibleTabSelector:function(d){a("showAccessibleTabSelector");var f=this;var e=b(d);
if(e){if(e.get(0).nodeName.toLowerCase()=="a"){e.click();}else{a("the selector of a showAccessibleTabSelector() call needs to point to a tabs headline!");
}}}});function a(e,d){if(c&&window.console&&window.console.log){if(d){window.console.log(d+": ",e);}else{window.console.log(e);}}}})(jQuery);