(()=>{"use strict";const e=window.React,t=window.wc.wcBlocksRegistry,i=window.wp.htmlEntities,n=window.wc.wcSettings,a=window.wp.i18n,l=(0,n.getSetting)("tiankii_data",{}),s=(0,a.__)("Tiankii Payments","woo-gutenberg-products-block"),r=l.title?(0,i.decodeEntities)(l.title):s,c=()=>(0,e.createElement)("div",null,l.description?(0,i.decodeEntities)(l.description):""),o={name:"tiankii",label:(0,e.createElement)((()=>(0,e.createElement)("div",{style:{display:"flex",alignItems:"center"}},"yes"===l.showImage&&l.image&&(0,e.createElement)("img",{src:l.image,alt:"Tiankii Logo",style:{marginRight:"10px"}}),(0,e.createElement)("span",null,r))),null),content:(0,e.createElement)(c,null),edit:(0,e.createElement)(c,null),canMakePayment:()=>!0,ariaLabel:r,supports:{features:l.supports}};(0,t.registerPaymentMethod)(o)})();