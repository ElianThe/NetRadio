<template>
  <div>
    <button @click="startStreaming">Commencer l'enregistrement</button>
    <button @click="stopStreaming">Arrêter l'enregistrement</button>
    <button @click="downloadWav">Télécharger au format WAV</button>
    <img src="@/assets/telechargement.jpg" alt="image">
  </div>
</template>

<script>
import axios from "axios";
import { ref, onMounted, onUnmounted } from "vue";
import { UserAgent, Session } from '@apirtc/apirtc'

export default {
  setup() {
    const localStream = ref(null);
    const conversation = ref(null);
    const audioContext = ref(null);
    const mediaStreamSource = ref(null);
    const mediaRecorder = ref(null);
    const isStreaming = ref(false);
    const recordedChunks = ref([]);

    const ua = new UserAgent({
      uri: "apzkey:myDemoApiKey",
    });

    const connectToServer = async () => {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        audioContext.value = new (window.AudioContext || window.webkitAudioContext)();
        mediaStreamSource.value = audioContext.value.createMediaStreamSource(stream);
        mediaRecorder.value = new MediaRecorder(stream);
        mediaRecorder.value.ondataavailable = (event) => {
          if (event.data.size > 0) {
            recordedChunks.value.push(event.data);
          }
        };
      } catch (error) {
        console.error("Error accessing microphone:", error);
      }
    };

    const startStreaming = async () => {
      await connectToServer();
      const session = await ua.register();
      const conversationInstance = session.getConversation('myConversation');
      conversation.value = conversationInstance;

      conversationInstance.on("streamListChanged", (streamInfo) => {
        if (streamInfo.listEventType === "added" && streamInfo.isRemote === true) {
          conversationInstance.subscribeToMedia(streamInfo.streamId)
            .then((stream) => {
              console.log("subscribeToMedia success", stream);
            })
            .catch((err) => {
              console.error("subscribeToMedia error", err);
            });
        }
      });

      conversationInstance
        .on("streamAdded", (stream) => {
          stream.addInDiv("remote-container", "remote-media-" + stream.streamId, {}, false);
        })
        .on("streamRemoved", (stream) => {
          stream.removeFromDiv("remote-container", "remote-media-" + stream.streamId);
        });

      const createStream = await ua.createStream({
        constraints: {
          audio: true,
          video: true,
        },
      });

      localStream.value = createStream;
      createStream.removeFromDiv("local-container", "local-media");
      createStream.addInDiv("local-container", "local-media", {}, true);

      const joinResponse = await conversationInstance.join();
      console.log("Conversation joined", joinResponse);

      const publishResponse = await conversationInstance.publish(localStream.value);
      console.log("published", publishResponse);

      isStreaming.value = true;
    };

    const stopStreaming = () => {
      if (mediaRecorder.value && isStreaming.value) {
        mediaRecorder.value.stop();
        isStreaming.value = false;

        const blob = new Blob(recordedChunks.value, { type: "audio/wav" });
        const emission = getEmission(); // This function needs to be defined
        if (emission) {
          const body = {
            titre: emission.titre,
            description: emission.description,
            audio: blob,
            emission_id: emission.id,
          };
          axios.post("http://localhost:2080/podcasts", body)
            .then((response) => {
              console.log(response);
            })
            .catch((error) => {
              console.error(error);
            });
        }
        downloadWav();
      }
    };

    const downloadWav = () => {
      if (recordedChunks.value.length > 0) {
        const blob = new Blob(recordedChunks.value, { type: 'audio/wav' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `emission${this.$route.params.id}.wav`;
        a.click();
        URL.revokeObjectURL(url);
      }
    };

    const getEmission = async () => {
      try {
        const response = await axios.get(`/emissions/${this.$route.params.id}`);
        return response.data.emission;
      } catch (error) {
        console.error(error);
        return null;
      }
    };

    onMounted(() => {
      startStreaming();
    });

    onUnmounted(() => {
      stopStreaming();
    });

    return { stopStreaming, localStream, conversation };
  },
};
</script>



<style scoped>
div {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: #FFFFFF;
  height: 100vh;
}

button {
  background-color: #A568BB;
  color: white;
  margin-bottom: 10px;
  cursor: pointer;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  font-size: 1rem;
}
</style>