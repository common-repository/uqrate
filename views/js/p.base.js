;(function(o, undefined){
    'use strict'
    /******************************************
     * @ Wordpress plugin : Uqrate 
     *****************************************/
    window.addEventListener('load', (ev) => {
        const 
            ver = '1.0.0'
            ,debug = true
            ,log = (arg, ...args) => debug && console.log(
                `𝐖 [${(new Date()).toISOString().substring(17)}] [v${ver}]` 
                ,arg, ...args
            )
            ,id = (id) => document.getElementById(id)
            ,css = (selector, root) => {
                root = root ? root : document 
                return root.querySelector(selector)
            }
            ,cssAll = (selector, root) => {
                root = root ? root : document 
                return root.querySelectorAll(selector)
            }
            ,cssClass = (name, root) => {
                root = root ? root : document 
                return root.getElementsByClassName(name)
            }
            ,tags = (tag, root) => {
                root = root ? root : document 
                return root.getElementsByTagName(tag)
            }
            ,main = tags('MAIN')[0]
            ,article = css('MAIN>ARTICLE')
            ,uqrate_comments = id('uqrate-comments')
            ,wp_comments = main && css('DIV.wp-block-post-comments', main)
            ,section_comments = css('#comments')
            ,iframe_ctnr_id = 'uqrate-iframe-container' 
            ,iframe_ctnr = id(iframe_ctnr_id)
            ,method = iframe_ctnr && iframe_ctnr.dataset.method

        // log(navigator, location)
        iframe_ctnr 
            ? log( 'IFRAME node rendered : method:', method, ': ARTICLE node: ', !!article ) 
            : log( `Missing IFRAME node: #${iframe_ctnr_id}` )

        // Reposition uqrate comments SECTION : abide native positioning.
        // main && uqrate_comments && main.appendChild(uqrate_comments)
        uqrate_comments 
            && article
                ? (uqrate_comments && article.after(uqrate_comments))
                : (main && uqrate_comments && main.appendChild(uqrate_comments))
        // TODO : create and render if uqrate_comments element not exist (requires data from backend).

        // Remove Wordpress-default comments DIV (already hidden by CSS)
        wp_comments && wp_comments.remove()
        section_comments && section_comments.remove()

    })
})()
