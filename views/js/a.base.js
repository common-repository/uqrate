;(function(o, undefined){
    'use strict'
    /******************************************
     * @ Wordpress plugin : Uqrate
     *****************************************/
    window.addEventListener('load', () => {
        const 
            ver = '1.0.0'
            ,debug = true
            ,log = (arg, ...args) => debug && console.log(
                `𝕎 [${(new Date()).toISOString().substring(17)}] [v${ver}]` 
                ,arg, ...args
            )
            ,css = (selector, root) => {
                root = root ? root : document 
                return root.querySelector(selector)
            }
            ,inputKey = css('#uqrate_settings_block FORM INPUT[name="uqrate_chnkey_key"]')
            ,settings = css('#uqrate_settings_block DD')
            ,update   = css('#uqrate_settings_update')
            ,statusButton = css('A', update)
            ,submit   = css('#uqrate_settings_block INPUT[name="submit"]')

            ,doStatusButton = (ev) => {
                ev.preventDefault()
                if (inputKey && settings && statusButton) {
                    inputKey.value = ''
                    settings.classList.remove('hide')
                    update.style.display = 'none'
                }
            }

        log(navigator, location)
        inputKey && inputKey.setAttribute('autofocus', true)
        inputKey && inputKey.setAttribute('tabindex', 0)
        inputKey && (inputKey.placeholder = 'Paste your Uqrate API key here.')
        statusButton && statusButton.addEventListener('click', doStatusButton)

    })
})()