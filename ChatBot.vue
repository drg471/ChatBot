<template>
    <v-container fluid>
        <v-row no-gutters>
            <!-- Columna izquierda: GIF -->
            <v-col
                cols="3"
                class="d-flex justify-center align-center pa-4 ml-10"
            >
                <v-row class="d-flex flex-column align-center justify-end">
                    <!-- GIF (fila superior) -->
                    <v-col
                        cols="12"
                        class="d-flex justify-center"
                        style="margin-top: 330px; margin-left: 30px"
                    >
                        <transition name="fade" mode="out-in">
                            <v-img
                                :key="currentGif"
                                :src="currentGif"
                                alt="Chat assistant GIF"
                                max-height="250"
                                contain
                                @load="onGifLoad"
                            ></v-img>
                        </transition>
                    </v-col>
                </v-row>
            </v-col>

            <!-- Columna derecha: Chat -->
            <v-col cols="6" class="">
                <!-- Snackbar -->
                <v-snackbar
                    v-model="sensitiveDataWarning"
                    color="error"
                    timeout="4500"
                >
                    <v-icon icon="mdi-alert-circle" class="mr-2" />
                    {{ warningMessage }}
                </v-snackbar>

                <!-- Modal -->
                <v-dialog v-model="warningDialog" persistent max-width="800">
                    <v-card>
                        <v-card-title class="text-h5">
                            <v-icon color="warning" class="mr-2"
                                >mdi-alert</v-icon
                            >
                            Advertencia Importante
                        </v-card-title>
                        <v-card-text>
                            <p class="text-body-1">
                                Por su seguridad,
                                <strong>NO COMPARTA</strong> información privada
                                o datos sensibles como contraseñas, números de
                                tarjetas de crédito, información médica o
                                cualquier dato personal confidencial.
                            </p>
                            <p class="text-body-1 mt-4">
                                Este chatbot está diseñado solo para preguntas
                                técnicas sobre informática y ofimática.
                            </p>
                            <p class="text-body-2 mt-4 font-italic">
                                Al hacer clic en "Aceptar", confirma que ha
                                leído y entendido esta advertencia.
                            </p>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer />
                            <v-btn
                                color="primary"
                                @click="acceptWarning"
                                variant="tonal"
                                >Aceptar y Continuar</v-btn
                            >
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <!-- Chat -->
                <v-card>
                    <v-card-title
                        class="bg-primary text-white d-flex ga-2 align-center py-2"
                    >
                        <v-avatar color="#3D7DC1" class="pt-2">
                            <v-img
                                src="/assets/images/chatbot/fs_avatar.png"
                                cover
                            />
                        </v-avatar>
                        <h3 class="mb-0">{{ siteName }}</h3>
                    </v-card-title>

                    <v-divider />

                    <v-card-text class="chat-messages" ref="messagesContainer">
                        <div
                            v-for="(msg, index) in messages"
                            :key="index"
                            :class="[
                                'message-row',
                                msg.role === 'user'
                                    ? 'user-row'
                                    : 'assistant-row',
                            ]"
                        >
                            <div
                                :class="[
                                    'message-bubble',
                                    msg.role === 'user'
                                        ? 'user-message'
                                        : 'assistant-message',
                                ]"
                            >
                                <div v-html="formatMessage(msg.content)" />
                            </div>
                        </div>

                        <div
                            v-if="loading && !isPredefinedQuestion"
                            class="message-row assistant-row"
                        >
                            <div
                                class="message-bubble assistant-message thinking-message"
                            >
                                <i>Pensando la respuesta...</i>
                            </div>
                        </div>
                        <v-progress-linear
                            v-if="loading && !isPredefinedQuestion"
                            indeterminate
                            color="primary"
                            class="mt-2"
                        />
                    </v-card-text>

                    <v-divider />

                    <v-card-actions>
                        <v-text-field
                            v-model="newMessage"
                            label="Escribe tu mensaje"
                            outlined
                            dense
                            hide-details
                            @keyup.enter="sendMessage"
                            :disabled="loading"
                            class="mr-2"
                            ref="messageInput"
                        />
                        <v-btn
                            color="primary"
                            @click="sendMessage"
                            :disabled="!newMessage || loading"
                            :loading="loading && !isPredefinedQuestion"
                            icon
                        >
                            <v-icon>mdi-send</v-icon>
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import axios from "axios";

export default {
    name: "ChatBot",
    data() {
        return {
            newMessage: "",
            messages: [],
            loading: false,
            warningDialog: true,
            warningAccepted: false,
            sensitiveDataWarning: false,
            warningMessage: "",
            isPredefinedQuestion: false,
            userActivityTimeout: null,
            isWaitingForUser: false,
            currentGif: "/assets/images/chatbot/fsanim9_idle.gif",
            gifs: {
                idle: "/assets/images/chatbot/fsanim9_idle.gif",
                responding: [
                    {
                        src: "/assets/images/chatbot/fsanim_responding_1.gif",
                        duration: 8500,
                    },
                    {
                        src: "/assets/images/chatbot/fsanim_responding_2.gif",
                        duration: 9000,
                    },
                    {
                        src: "/assets/images/chatbot/fsanim_responding_3.gif",
                        duration: 9000,
                    },
                    {
                        src: "/assets/images/chatbot/fsanim_responding_4.gif",
                        duration: 4500,
                    },
                ],
                waiting: [
                    {
                        src: "/assets/images/chatbot/fsanim_waiting_1.gif",
                        duration: 9000,
                    },
                    {
                        src: "/assets/images/chatbot/fsanim_waiting_2.gif",
                        duration: 9000,
                    },
                    {
                        src: "/assets/images/chatbot/fsanim9_idle.gif",
                        duration: 9000,
                    },
                ],
                thinking: [
                    {
                        src: "/assets/images/chatbot/fsanim_thinking2.gif",
                        duration: 9000,
                    },
                ],
                sad: [
                    {
                        src: "/assets/images/chatbot/fsanim_sad_1.gif",
                        duration: 4500,
                    },
                ],
                angry: [
                    {
                        src: "/assets/images/chatbot/fsanim_angry_1.gif",
                        duration: 4500,
                    },
                    {
                        src: "/assets/images/chatbot/fsanim_angry_2.gif",
                        duration: 4500,
                    },
                    {
                        src: "/assets/images/chatbot/fsanim_angry_3.gif",
                        duration: 4500,
                    },
                ],
            },
            gifPlaybackTimeout: null, 
            siteName: "Dinobot",
        };
    },
    methods: {
        acceptWarning() {
            this.warningAccepted = true;
            this.warningDialog = false;
            localStorage.setItem("warningAccepted", "true");
        },

        formatMessage(text) {
            return (
                text
                    // Encabezados
                    .replace(/^# (.*$)/gm, "<h1>$1</h1>")
                    .replace(/^## (.*$)/gm, "<h2>$1</h2>")
                    .replace(/^### (.*$)/gm, '<h3 class="md-h3">$1</h3>')
                    .replace(/^#### (.*$)/gm, '<h4 class="md-h4">$1</h4>')

                    // Énfasis
                    .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
                    .replace(/\*(.*?)\*|_(.*?)_/g, "<em>$1$2</em>")
                    .replace(/~~(.*?)~~/g, "<del>$1</del>")

                    // Código
                    .replace(/`([^`]+)`/g, "<code>$1</code>")
                    .replace(
                        /```[\s\S]*?```/g,
                        (match) =>
                            `<pre>${match.replace(
                                /```[\w]*\n?|\n?```$/g,
                                ""
                            )}</pre>`
                    )

                    // Procesamiento para bloques |||
                    .replace(
                        /\|\|\|([^\n]+)\|\|\|/g,
                        '<div class="custom-block">$1</div>'
                    )

                    // Listas
                    .replace(/^[-*] (.*$)/gm, "<li>$1</li>")
                    .replace(/^[0-9]+\. (.*$)/gm, "<li>$1</li>")

                    // Citas
                    .replace(/^> (.*$)/gm, "<blockquote>$1</blockquote>")

                    // Imágenes (![alt](url)) - Versión mejorada
                    .replace(
                        /!\[([^\]]+)\]\(([^)\s]+)(?:\s+"([^"]+)")?\)/g,
                        (match, altText, url, title) => {
                            // Verificar si es una URL válida o podría ser texto alternativo
                            if (!/^(https?:|\/)/.test(url)) {
                                return `![${altText}](${url})`; // Devolver original si no es URL
                            }
                            return `<img src="${url}" alt="${altText}" ${
                                title ? `title="${title}"` : ""
                            } class="md-image">`;
                        }
                    )

                    // Enlaces ([texto](url)) - Versión más segura
                    .replace(
                        /\[([^\]]+)\]\(([^)\s]+)(?:\s+"([^"]+)")?\)/g,
                        '<a href="$2" target="_blank" rel="noopener noreferrer" $3 ? `title="$3"` : ""}>$1</a>'
                    )

                    // Líneas divisorias
                    .replace(/^---$/gm, "<hr>")

                    // Saltos de línea
                    .replace(/\n/g, "<br>")
            );
        },

        async sendMessage() {
            if (!this.newMessage.trim()) return;

            this.messages.push({
                role: "user",
                content: this.newMessage,
            });

            this.$nextTick(() => {
                setTimeout(() => {
                    this.scrollToBottom();
                }, 100);
            });

            const userMessage = this.newMessage;
            this.newMessage = "";

            this.isPredefinedQuestion = false;
            this.loading = true;

            this.resetUserActivityTimeout();

            try {
                const response = await this.getAIResponse(userMessage);

                if (response.containsSensitiveData) {
                    return;
                }

                if (response.includes("Lo siento, solo puedo responder")) {
                    this.playSadGif();
                } else {
                    this.playRespondingGif();
                }

                this.messages.push({
                    role: "assistant",
                    content: response,
                });
            } catch (error) {
                console.error("Error en la API:", error);

                let errorMessage;
                if (error.status === 429) {
                    errorMessage =
                        "⚠️ Estoy recibiendo muchas solicitudes ahora mismo. Por favor, espera 1-2 minutos y vuelve a intentarlo.";
                } else if (
                    error.message &&
                    error.message.includes("Failed to fetch")
                ) {
                    errorMessage =
                        "🔌 Error de conexión. Por favor, verifica tu conexión a internet e inténtalo nuevamente.";
                } else {
                    errorMessage =
                        "❌ Lo siento, hubo un error procesando tu solicitud. Por favor, inténtalo más tarde.";
                }

                this.messages.push({
                    role: "assistant",
                    content: errorMessage,
                });
            } finally {
                this.$nextTick(() => {
                    this.$refs.messageInput?.focus();
                });
            }
        },

        handlePickLinkClick(event) {
            this.isPredefinedQuestion = true;
            this.resetUserActivityTimeout();

            this.playRespondingGif();

            event.preventDefault();
            const answerType = event.target.getAttribute("data-answer");

            let respuesta;
            let userMessage;

            if (answerType === "preguntas-informatica") {
                userMessage = "Preguntas frecuentes de informática";
                respuesta =
                    "**💻 Preguntas frecuentes de informática:**\n\n" +
                    "<a href='#' class='pick-link' data-answer='reiniciar-pc'>- ¿Cómo reiniciar correctamente mi PC cuando se bloquea?</a>\n\n" +
                    "<a href='#' class='pick-link' data-answer='velocidad-internet'>- ¿Cómo comprobar la velocidad de mi conexión a Internet?</a>\n\n" +
                    "<a href='#' class='pick-link' data-answer='virus'>- ¿Qué hacer si creo que mi computadora tiene un virus?</a>\n\n" +
                    "<a href='#' class='pick-link' data-answer='backup'>- ¿Cómo hacer una copia de seguridad de mis archivos importantes?</a>\n\n" +
                    "<a href='#' class='pick-link' data-answer='wifi-lento'>- ¿Cómo solucionar problemas de conexión WiFi lenta?</a>";
            } else if (answerType === "reiniciar-pc") {
                userMessage =
                    "¿Cómo reiniciar correctamente mi PC cuando se bloquea?";
                respuesta =
                    "**🔄 Reinicio forzado de PC (cuando se bloquea):**\n\n" +
                    "1. **Mantén presionado** el botón de encendido durante 5-10 segundos hasta que se apague\n" +
                    "2. **Espera** 10-15 segundos antes de volver a encender\n" +
                    "3. **Enciende** normalmente\n\n" +
                    "|||⚠️ **Nota importante:** Este método solo debe usarse cuando el sistema no responde. No es recomendable para uso frecuente ya que puede dañar archivos del sistema.|||\n\n" +
                    "¿Necesitas ayuda con otro tema? <a href='#' class='pick-link' data-answer='preguntas-informatica'>- Ver preguntas frecuentes</a>";
            } else if (answerType === "velocidad-internet") {
                userMessage =
                    "¿Cómo comprobar la velocidad de mi conexión a Internet?";
                respuesta =
                    "**📶 Comprobar velocidad de Internet:**\n\n" +
                    "1. **Conéctate** directamente por cable Ethernet si es posible (para prueba más precisa)\n" +
                    "2. **Cierra** programas que usen Internet\n" +
                    "3. **Visita** <a href='https://www.speedtest.net' target='_blank'>speedtest.net</a> o <a href='https://fast.com' target='_blank'>fast.com</a>\n" +
                    "4. **Haz clic** en 'Iniciar prueba'\n\n" +
                    "|||📊 **Valores normales:**\n- Bajas: <10 Mbps\n- Medias: 10-50 Mbps\n- Buenas: 50-100 Mbps\n- Muy buenas: >100 Mbps|||\n\n" +
                    "¿Necesitas ayuda con otro tema? <a href='#' class='pick-link' data-answer='preguntas-informatica'>- Ver preguntas frecuentes</a>";
            } else if (answerType === "virus") {
                userMessage =
                    "¿Qué hacer si creo que mi computadora tiene un virus?";
                respuesta =
                    "**🦠 Posible infección por virus:**\n\n" +
                    "1. **Desconecta** el equipo de Internet\n" +
                    "2. **Ejecuta** un escaneo completo con tu antivirus\n" +
                    "3. **Usa** herramientas especializadas como Malwarebytes\n" +
                    "4. **Cambia** todas tus contraseñas importantes desde otro dispositivo seguro\n" +
                    "5. **Considera** restaurar el sistema a un punto anterior\n\n" +
                    "|||🔒 **Prevención:**\n- Mantén tu sistema operativo actualizado\n- No abras archivos adjuntos sospechosos\n- Usa un buen antivirus|||\n\n" +
                    "¿Necesitas ayuda con otro tema? <a href='#' class='pick-link' data-answer='preguntas-informatica'>- Ver preguntas frecuentes</a>";
            } else if (answerType === "backup") {
                userMessage =
                    "¿Cómo hacer una copia de seguridad de mis archivos importantes?";
                respuesta =
                    "**💾 Copias de seguridad:**\n\n" +
                    "**Método 1 - USB/Disco externo:**\n" +
                    "1. Conecta tu dispositivo de almacenamiento\n" +
                    "2. Copia manualmente tus archivos importantes\n\n" +
                    "**Método 2 - Nube:**\n" +
                    "1. Usa servicios como Google Drive, OneDrive o Dropbox\n" +
                    "2. Configura sincronización automática si es posible\n\n" +
                    "**Método 3 - Software especializado:**\n" +
                    "1. Usa herramientas como Macrium Reflect (Windows) o Time Machine (Mac)\n" +
                    "2. Programa copias automáticas periódicas\n\n" +
                    "|||📅 **Recomendación:** Haz copias de seguridad al menos una vez por semana si trabajas con archivos importantes.|||\n\n" +
                    "¿Necesitas ayuda con otro tema? <a href='#' class='pick-link' data-answer='preguntas-informatica'>- Ver preguntas frecuentes</a>";
            } else if (answerType === "wifi-lento") {
                userMessage =
                    "¿Cómo solucionar problemas de conexión WiFi lenta?";
                respuesta =
                    "**🐌 WiFi lento - Soluciones:**\n\n" +
                    "1. **Reinicia** el router/módem (apágalo 30 segundos)\n" +
                    "2. **Acércate** al router o elimina obstáculos\n" +
                    "3. **Cambia** el canal WiFi (usa apps como WiFi Analyzer)\n" +
                    "4. **Conecta** dispositivos importantes por cable\n" +
                    "5. **Actualiza** los drivers de tu tarjeta de red\n\n" +
                    "|||📡 **Dato técnico:** La banda de 5GHz es más rápida pero tiene menos alcance que la de 2.4GHz.|||\n\n" +
                    "¿Necesitas ayuda con otro tema? <a href='#' class='pick-link' data-answer='preguntas-informatica'>- Ver preguntas frecuentes</a>";
            }

            this.messages.push({
                role: "user",
                content: userMessage,
            });
            this.messages.push({
                role: "assistant",
                content: respuesta,
            });

            const artificialDelayForAnimationGIF = 9000;

            this.$nextTick(() => {
                this.scrollToBottom();
            });

            setTimeout(() => {
                this.isPredefinedQuestion = false;
                this.loading = false;
            }, artificialDelayForAnimationGIF);
        },

        async getAIResponse(userMessage) {
            const messagesForAPI = [
                ...this.messages.map((msg) => ({
                    role: msg.role,
                    content: msg.content,
                })),
                {
                    role: "user",
                    content: userMessage,
                },
            ];

            try {
                const response = await axios.post(
                    "/chatbot/get-response",
                    {
                        messages: messagesForAPI,
                        temperature: 0.3,
                    },
                    {
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                        },
                    }
                );

                if (response.data.containsSensitiveData) {
                    this.warningMessage = response.data.message;
                    this.sensitiveDataWarning = true;
                    this.loading = false;
                    this.playAngryGif();

                    return {
                        containsSensitiveData: true,
                    };
                }

                return (
                    response.data.response ||
                    "No recibí respuesta del Back-end."
                );
            } catch (error) {
                console.error("Error en la API:", error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        scrollToBottom() {
            let container = this.$refs.messagesContainer;
            if (container && container.$el) {
                container = container.$el;
            }
            if (container && typeof container.scrollTo === "function") {
                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: "smooth",
                });
            } else if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        preloadGifs() {
            // Precargar todos los GIFs posibles
            [this.gifs.idle].forEach((gif) => {
                new Image().src = gif;
            });

            // Precargar GIFs de respuesta
            this.gifs.responding.forEach((gifObj) => {
                new Image().src = gifObj.src;
            });

            // Precargar GIFs de espera
            this.gifs.waiting.forEach((gifObj) => {
                new Image().src = gifObj.src;
            });

            // Precargar GIFs de tristeza
            this.gifs.sad.forEach((gifObj) => {
                new Image().src = gifObj.src;
            });

            // Precargar GIFs de enfado
            this.gifs.angry.forEach((gifObj) => {
                new Image().src = gifObj.src;
            });
        },

        onGifLoad() {
            // Este método se dispara cuando el GIF se carga
            console.log("GIF cargado:", this.currentGif);
        },

        setRandomRespondingGifAndRevert() {
            const respondingGifs = this.gifs.responding;
            if (respondingGifs.length === 0) {
                this.currentGif = this.gifs.idle;
                return;
            }

            const randomIndex = Math.floor(
                Math.random() * respondingGifs.length
            );
            const selectedGif = respondingGifs[randomIndex];

            if (this.gifPlaybackTimeout) {
                clearTimeout(this.gifPlaybackTimeout);
            }

            // Cambiar al GIF de respuesta
            this.currentGif = selectedGif.src;
            console.log(
                "Activando GIF de respuesta:",
                selectedGif.src,
                "duración:",
                selectedGif.duration
            );

            this.gifPlaybackTimeout = setTimeout(() => {
                this.currentGif = this.gifs.idle; // Vuelve al idle cuando termina la respuesta
                this.resetUserActivityTimeout();
            }, selectedGif.duration);
        },

        resetUserActivityTimeout() {
            // Limpia cualquier temporizador existente
            if (this.userActivityTimeout) {
                clearTimeout(this.userActivityTimeout);
            }
            // Desactiva el estado de espera inmediatamente si el usuario interactúa
            this.isWaitingForUser = false;
            this.currentGif = this.gifs.idle; // Vuelve al GIF idle cuando hay actividad

            // Establece un nuevo temporizador
            const delay = 30000;

            this.userActivityTimeout = setTimeout(() => {
                // Solo activa el modo de espera si no estamos en modo "loading" (respondiendo a una pregunta)
                if (!this.loading) {
                    this.isWaitingForUser = true;
                    this.setRandomWaitingGif(); // Llama a una nueva función para los GIFs de espera
                }
            }, delay);
        },

        setRandomWaitingGif() {
            const waitingGifs = this.gifs.waiting;
            if (waitingGifs.length === 0) {
                this.currentGif = this.gifs.idle;
                return;
            }

            const randomIndex = Math.floor(Math.random() * waitingGifs.length);
            const selectedGif = waitingGifs[randomIndex];

            // Limpiar cualquier temporizador de reproducción de GIF anterior
            if (this.gifPlaybackTimeout) {
                clearTimeout(this.gifPlaybackTimeout);
            }

            this.currentGif = selectedGif.src;
            console.log(
                "Activando GIF de espera:",
                selectedGif.src,
                "duración:",
                selectedGif.duration
            );

            // Configura un temporizador para revertir al GIF idle después de la duración del GIF de espera
            // O puedes encadenar la reproducción de múltiples GIFs de espera si lo deseas
            this.gifPlaybackTimeout = setTimeout(() => {
                // Si el usuario sigue inactivo, elegimos otro GIF de espera o volvemos al idle
                if (this.isWaitingForUser && !this.loading) {
                    this.setRandomWaitingGif(); // Reproduce otro GIF de espera
                } else {
                    this.currentGif = this.gifs.idle; // Si el usuario ya no está esperando, vuelve al idle
                }
            }, selectedGif.duration);
        },

        playRespondingGif() {
            const respondingGifs = this.gifs.responding;
            if (respondingGifs.length === 0) return;

            // Limpiar cualquier temporizador existente
            if (this.gifPlaybackTimeout) {
                clearTimeout(this.gifPlaybackTimeout);
            }

            // Selección aleatoria
            const randomIndex = Math.floor(
                Math.random() * respondingGifs.length
            );
            const respondingGif = respondingGifs[randomIndex];

            // Cambiar al GIF de respuesta
            this.currentGif = respondingGif.src;
            console.log(
                "Activando GIF de respuesta:",
                respondingGif.src,
                "duración:",
                respondingGif.duration
            );

            // Configurar el temporizador para volver al GIF idle después de la duración
            this.gifPlaybackTimeout = setTimeout(() => {
                this.currentGif = this.gifs.idle;
            }, respondingGif.duration);
        },

        playThinkingGif() {
            const thinkingGifs = this.gifs.thinking;
            if (thinkingGifs.length === 0) return;

            // Limpiar cualquier temporizador existente
            if (this.gifPlaybackTimeout) {
                clearTimeout(this.gifPlaybackTimeout);
            }

            // Seleccionar el primer GIF de thinking (puedes hacerlo aleatorio si tienes varios)
            const thinkingGif = thinkingGifs[0];

            // Cambiar al GIF thinking
            this.currentGif = thinkingGif.src;
            console.log("Activando GIF thinking:", thinkingGif.src);

            // Configurar el temporizador para volver al GIF idle después de la duración
            // (aunque normalmente el loading cambiará a false antes)
            this.gifPlaybackTimeout = setTimeout(() => {
                if (this.loading) {
                    // Si todavía está cargando después de la duración
                    this.currentGif = this.gifs.idle; // O podrías poner otro GIF
                }
            }, thinkingGif.duration);
        },

        playSadGif() {
            const sadGifs = this.gifs.sad;
            if (!sadGifs || sadGifs.length === 0) return;

            // Limpiar cualquier temporizador existente
            if (this.gifPlaybackTimeout) {
                clearTimeout(this.gifPlaybackTimeout);
            }

            // Selección aleatoria (aunque sólo tienes uno, esto permite escalar)
            const randomIndex = Math.floor(Math.random() * sadGifs.length);
            const sadGif = sadGifs[randomIndex];

            // Cambiar al GIF sad
            this.currentGif = sadGif.src;
            console.log(
                "Activando GIF sad:",
                sadGif.src,
                "duración:",
                sadGif.duration
            );

            // Configurar el temporizador para volver al GIF idle después de la duración
            this.gifPlaybackTimeout = setTimeout(() => {
                this.currentGif = this.gifs.idle;
            }, sadGif.duration);
        },

        playAngryGif() {
            const angryGifs = this.gifs.angry;
            if (angryGifs.length === 0) return;

            // Limpiar cualquier temporizador existente
            if (this.gifPlaybackTimeout) {
                clearTimeout(this.gifPlaybackTimeout);
            }

            // Selección aleatoria
            const randomIndex = Math.floor(Math.random() * angryGifs.length);
            const angryGif = angryGifs[randomIndex];

            // Cambiar al GIF angry
            this.currentGif = angryGif.src;
            console.log(
                "Activando GIF angry:",
                angryGif.src,
                "duración:",
                angryGif.duration
            );

            // Configurar el temporizador para volver al GIF idle después de la duración
            this.gifPlaybackTimeout = setTimeout(() => {
                this.currentGif = this.gifs.idle;
            }, angryGif.duration);
        },
    },
    watch: {
        messages: {
            deep: true,
            handler() {
                this.$nextTick(() => {
                    document.querySelectorAll(".pick-link").forEach((link) => {
                        link.addEventListener(
                            "click",
                            this.handlePickLinkClick
                        );
                    });
                });
            },
        },
    },
    mounted() {
        // Verificar si ya aceptó la advertencia anteriormente
        const accepted = localStorage.getItem("warningAccepted");
        if (accepted === "true") {
            this.warningAccepted = true;
        } else {
            this.warningDialog = true;
        }

        this.preloadGifs();
        this.resetUserActivityTimeout();

        this.messages.push({
            role: "assistant",
            content:
            "¡Hola! 👋 \n\n Soy un asistente especializado en informática. Cuéntame, ¿en qué problema técnico necesitas ayuda hoy?\n\nTambién puedes seleccionar directamente una de estas preguntas frecuentes:\n\n\t<a href='#' class='pick-link' data-answer='preguntas-informatica'>- Ver preguntas frecuentes de informática</a>",        });
        this.$nextTick(() => {
            this.scrollToBottom();
        });
    },
    beforeUnmount() {
        if (this.userActivityTimeout) {
            clearTimeout(this.userActivityTimeout);
        }
        const chatInput = this.$el.querySelector('input[type="text"]');
        if (chatInput) {
            chatInput.removeEventListener(
                "input",
                this.resetUserActivityTimeout
            );
        }
    },
};
</script>

<style scoped>
.chat-messages {
    height: 550px;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    /* Activa Flexbox */
    flex-direction: column;
    /* Apila los mensajes verticalmente */
    gap: 12px;
    /* Espacio entre las burbujas de chat */
}

/* Contenedor de cada fila de mensaje */
.message-row {
    display: flex;
    width: 100%;
}

/* Alinea las filas del asistente a la izquierda */
.assistant-row {
    justify-content: flex-start;
}

/* Alinea las filas del usuario a la derecha */
.user-row {
    justify-content: flex-end;
}

/* Estilo base para todas las burbujas de chat */
.message-bubble {
    max-width: 80%;
    /* Las burbujas no ocupan más del 80% del ancho */
    padding: 12px 16px;
    border-radius: 18px;
    /* Bordes más redondeados */
    word-wrap: break-word;
    /* Asegura que el texto largo no se desborde */
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Burbuja para los mensajes del asistente (izquierda) */
.assistant-message {
    background-color: #f0f0f0;
    /* Un gris claro, típico para el receptor */
    color: #333;
    border-top-left-radius: 4px;
    /* Esquina menos redondeada para dar forma de "cola" */
}

/* Burbuja para los mensajes del usuario (derecha) */
.user-message {
    background-color: #dcf8c6;
    /* Un verde claro, como en WhatsApp */
    color: #333;
    border-top-right-radius: 4px;
    /* Esquina menos redondeada en el otro lado */
}

.thinking-message {
    font-style: italic;
    color: #555;
    background-color: #f0f0f0;
    /* Mismo fondo que un mensaje normal del asistente */
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
