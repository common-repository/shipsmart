<?php

declare( strict_types = 1 );

namespace ShipSmart\Providers\Config;

use ShipSmart\Entities\ShipSmart;
use ShipSmart\Services\SSFW_ApiService;
use WPSteak\Providers\AbstractHookProvider;

// phpcs:ignoreFile
class SSFW_Menu extends AbstractHookProvider {
    public function register_hooks(): void {
        add_action( 'admin_menu', array( $this, 'build_menu' ), 99 );
        add_action( 'admin_post_save_general_settings', array( $this, 'save_general_settings' ), 99 );
        add_action( 'admin_post_save_orders_settings', array( $this, 'save_orders_settings' ), 99 );
    }

    public function build_menu(): void {
        add_submenu_page(
            'woocommerce',
            __( 'ShipSmart', 'shipsmart' ),
            __( 'ShipSmart', 'shipsmart' ),
            'edit_posts',
            ShipSmart::MENU_NAME,
            array( $this, 'build_page' ),
            25
        );
    }

    public function save_orders_settings() {
        $inputs_post = filter_input_array( INPUT_POST );

        if ( ! isset( $inputs_post['page'] ) || $inputs_post['page'] !== 'shipsmart' ) {
            wp_redirect( site_url() );
        }

    }

    public function save_general_settings() {
        $inputs_post = filter_input_array( INPUT_POST );

        if ( ! isset( $inputs_post['page'] ) || $inputs_post['page'] !== 'shipsmart' ) {
            wp_redirect( site_url() );
        }

        if ( isset( $inputs_post['apikey_shipsmart'] ) ) {
            
            $this->save_setting( 'apikey_shipsmart' );
            $valid = SSFW_ApiService::valid_api_key( $inputs_post['apikey_shipsmart'] );
            update_option( 'ss_api_verification', $valid ? 'yes' : 'no', true );
            update_option( 'ss_show_api_verification', true, true );

        }
        
        wp_redirect( admin_url( 'admin.php?page=' . $inputs_post['page'] ) );
    }


    public function save_setting( $name ): bool {
        $inputs_post = filter_input_array( INPUT_POST );
        $name_formatted = ShipSmart::PREFIX_NAME . $name;

        if ( ! isset( $inputs_post[$name] ) ) {
            return false;
        }

        update_option( $name_formatted, sanitize_text_field( $inputs_post[$name] ), true );

        return true;
    }

    public function build_page(): void {
        $api_key_valid = get_option( 'ss_api_verification' );
        ?>
            <div class="wrap">
                <?php if (false !== $api_key_valid && get_option( 'ss_show_api_verification' ) ) {
                        if ( $api_key_valid === 'yes' ) {
                            delete_option( 'ss_show_api_verification' );
                ?>
                            <div class="notice notice-success is-dismissible">
                                <p>API KEY foi conectada com sucesso!</p>
                            </div>
                <?php   } else { ?>
                            <div class="notice notice-error is-dismissible">
                                <p>API KEY está incorreta, verifique novamente!</p>
                            </div>
                <?php   }
                    }?>


                <h1 class="Shipsmart__settings-title">ShipSmart</h1>
                <div class="Shipsmart__settings-content">
                    <div class="Shipsmart__settings-tab">
                        <button class="Shipsmart__settings-tablink" onclick="openTab(event, 'general')" id="defaultTab">Geral</button>
                        <button class="Shipsmart__settings-tablink" onclick="openTab(event, 'orders')" >Pedidos</button>
                        <button class="Shipsmart__settings-tablink" onclick="openTab(event, 'tutorial')" >Tutorial</button>
                    </div>

                    <form class="Shipsmart__settings-form" method='POST' action="<?php echo esc_url( admin_url( 'admin-post.php' )  );?>" id="general">
                        <input type="hidden" name="page" value="shipsmart">
                        <input type="hidden" name="action" value="save_general_settings">

                        <span class="Shipsmart__settings-description--acount">Para ativar a SHIPSMART você precisa de uma conta.</span>
                        <span class="Shipsmart__settings-description--acount">Caso não tenha conta, <a href="https://shipsmart.com.br/cadastro/" target="_blank" class="Shipsmart__settings-link">Clique aqui</a> e crie sua conta. Obs: O Token chegará no e-mail cadastrado na ShipSmart.</span>

                        <span class="Shipsmart__settings-description">Já possui uma conta? Confira o e-mail de confirmação da criação de conta e copie o Token.</span>
                        
                        <div class="Shipsmart__settings-inputs">
                            <label class="Shipsmart__settings-label" for="apikey_shipsmart">API KEY:</label>
                            <input
                                class="Shipsmart__settings-input"
                                value="<?php echo esc_attr( get_option( ShipSmart::PREFIX_NAME . 'apikey_shipsmart' ) ); ?>"
                                type="text"
                                name="apikey_shipsmart"
                                id="apikey_shipsmart"
                                placeholder="Coloque a sua API KEY aqui"
                            >

                            <?php if (false !== $api_key_valid ) {
                                    if ( $api_key_valid === 'yes' ) { ?>
                                        <div class="Shipsmart__settings-checked">
                                            <svg  xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                            </svg>
                                        </div>
                            <?php   } else { ?>
                                        <div class="Shipsmart__settings-unchecked">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                            </svg>
                                        </div>
                            <?php   }
                                }?>
                        </div>

                        <button class="Shipsmart__settings-save-button">Salvar configurações</button>
                    </form>

                    <form class="Shipsmart__settings-form" method='POST' action="<?php echo esc_url( admin_url( 'admin-post.php' )  );?>" id="orders">
                        <input type="hidden" name="page" value="shipsmart">
                        <input type="hidden" name="action" value="save_orders_settings">

                        <div class="Shipsmart__settings-inputs">
                            <label class="Shipsmart__settings-label" style="margin-right: 10vw;">Deseja atualizar todos os status de pedidos registrados?</label>
                            <input type="button" style="margin-top: 0;" class="Shipsmart__settings-input Shipsmart__settings-save-button" id="update_orders_button" onclick="updateOrders()" value="Atualizar pedidos"></button>
                        </div>

                        <hr class="Shipsmart__settings-separator">

                        <div class="Shipsmart__settings-inputs">
                            <label class="Shipsmart__settings-label" style="margin-right: 5vw;">Cadastrar nova caixa</label>
                            <label class="Shipsmart__measures-label" style="margin-right: 1vh;" for="">Peso (kg):</label>
                            <input type="number" id='box_weight' min="0" class="Shipsmart__settings-measure" value="Atualizar pedidos"></button>
                            <label class="Shipsmart__measures-label" style="margin-right: 1vh;" for="">Altura (cm):</label>
                            <input type="number" id='box_height' min="0" class="Shipsmart__settings-measure" value="Atualizar pedidos"></button>
                            <label class="Shipsmart__measures-label" style="margin-right: 1vh;" for="">Largura (cm):</label>
                            <input type="number" id='box_width' min="0" class="Shipsmart__settings-measure" value="Atualizar pedidos"></button>
                            <label class="Shipsmart__measures-label" style="margin-right: 1vh;" for="">Comprimento (cm):</label>
                            <input type="number" id='box_length' min="0" class="Shipsmart__settings-measure" value="Atualizar pedidos"></button>
                            <input type="button" class="Shipsmart__settings-plus button button-primary" onclick="createBoxDimensions()" value="Adicionar"></button>
                        </div>

                        <hr class="Shipsmart__settings-separator">

                        <div class="Shipsmart__settings-inputs">
                            <label style="margin-right: 5vw;">Caixas disponíveis</label>
                            <select name="" style="margin-right: 1vh;" class="Shipsmart__settings-select" id=""></select>
                            <input type="button" style="margin-right: 1vh;" class="Shipsmart__settings-minus button button-primary" onclick="removeBox()" value="Deletar caixa"></button>
                            <button type="button" style="margin-top: 0;" class="Shipsmart__settings-save-button" onclick="saveBoxesDimensions()" id="save_boxes">Salvar Caixas</button>
                        </div>

                        <hr class="Shipsmart__settings-separator">

                    </form>

                    <form class="Shipsmart__settings-form" id="tutorial">
                        <section class="Shipsmart__tutorial-section">
                            <h2 class="Shipsmart__tutorial-title">Instalação e ativação</h2>
                            <ol class="Shipsmart__tutorial-steps">
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Faça upload deste plugin em seu WordPress, e ative-o;</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Entre no menu lateral “WooCommerce > ShipSmart”;</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Ative a API KEY da ShipSmart.</span>
                                </li>
                            </ol>
                        </section>

                        <section class="Shipsmart__tutorial-section">
                            <h2 class="Shipsmart__tutorial-title">Configurações gerais do plugin</h2>
                            <ol class="Shipsmart__tutorial-steps">
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Após ativar a API KEY, configurar endereço da loja  “WooCommerce > Configurações > Geral > Endereço da loja”</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Configurar as áreas de entrega “WooCommerce > Configurações > Entrega > Áreas de entrega” e adicionar o método de entrega “ShipSmart”;</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Habilitar a opção de entrega “ShipSmart”;</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Editar as configurações de “Dados da loja”, “Taxas”, “Prazo adicional”; “Renomear rótulos”;</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Salvar alterações.</span>
                                </li>
                            </ol>
                        </section>

                        <section class="Shipsmart__tutorial-section">
                            <h2 class="Shipsmart__tutorial-title">Envio de pedidos para a ShipSmart</h2>
                            <ol class="Shipsmart__tutorial-steps">
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Abrir o novo pedido gerado em “WooCommerce > Pedidos”;</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Dentro da edição do pedido, ir na seção “Dados Frete - ShipSmart”;</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Na seção “Dados Frete - ShipSmart” adicionar a chave da nota e definir o tipo de caixa.</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Após preenchido, clicar no botão “Sincronizar”;</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Após sincronizar, aguardar atualização automática do status do pedido ou ir em “WooCommerce > ShipSmart > Pedidos” e clicar no botão “Atualizar pedidos”.</span>
                                </li>
                            </ol>
                        </section>

                        <section class="Shipsmart__tutorial-section">
                            <h2 class="Shipsmart__tutorial-title">Observações gerais</h2>
                            <ol class="Shipsmart__tutorial-steps">
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Necessário informar o HS Code! (O campo está disponível na edição do produto, em “Dadas do produto > Aba: Inventário”;</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Para configurar caixas personalizadas de envio da loja, ir em “WooCommerce > ShipSmart > Pedidos” e adicionar o tamanho das caixas e clicar no botão Salvar;</span>
                                </li>
                                <li class="Shipsmart__tutorial-step">
                                    <span class="Shipsmart__tutorial-label">Os documentos para impressão e rastreamento estão disponíveis no pedido gerado.</span>
                                </li>
                            </ol>
                        </section>
            
                        <section class="Shipsmart__tutorial-section">
                            <h2 class="Shipsmart__tutorial-title">Dúvidas?</h2>
                            <ul class="Shipsmart__tutorial-steps">
                                <li class="Shipsmart__tutorial-step">
                                    <a href="https://shipsmart.com.br/tire-suas-duvidas/" target="_blank" class="Shipsmart__tutorial-label">Sobre a solução ShipSmart.</a>
                                </li>
                            </ul>
                        </section>
                    </form>
                </div>
            </div>

            <script>
                document.getElementById("defaultTab").click();

                function openTab(evt, tabName) {
                    // Declare all variables
                    var i, tabcontent, tablinks;
                    
                    // Get all elements with class="tabcontent" and hide them
                    tabcontent = document.getElementsByClassName("Shipsmart__settings-form");
                    for (i = 0; i < tabcontent.length; i++) {
                        tabcontent[i].style.display = "none";
                    }
                    
                    // Get all elements with class="tablinks" and remove the class "active"
                    tablinks = document.getElementsByClassName("Shipsmart__settings-tablink");
                    for (i = 0; i < tablinks.length; i++) {
                        tablinks[i].className = tablinks[i].className.replace(" active", "");
                    }
                    
                    // Show the current tab, and add an "active" class to the button that opened the tab
                    document.getElementById(tabName).style.display = "flex";
                    evt.currentTarget.className += " active";
                }
            </script>
        <?php
    }
}
