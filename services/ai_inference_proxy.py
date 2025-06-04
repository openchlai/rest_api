import socket
import multiprocessing
import os
import pycurl
import io
import wave
import json
import base64
import time

def wrap_raw_audio_with_wav(data, sample_rate=16000, channels=1, sampwidth=2):
	# Wraps raw 16-bit signed PCM data with a WAV header
	wav_io = io.BytesIO()
	wav_file = wave.open(wav_io, 'wb')
	wav_file.setnchannels(channels)
	wav_file.setsampwidth(sampwidth)  # 2 bytes = 16 bits
	wav_file.setframerate(sample_rate)
	wav_file.writeframes(data)
	return wav_io.getvalue()

def post_to_crm(uid, content_type, content, url="https://demo-openchs.bitz-itc.com/helpline/api/msg/"):
	status_code = 0
	response = "";
	msg_id = str(time.time());
	msg_ts = str(time.time());
	auth_token = os.getenv('HELPLINE_AUTH_TOKEN')
	jo = {
		"channel":"aii",
		"session_id":uid.decode('utf-8'),
		"message_id":msg_id,
		"timestamp":msg_ts,
		"from":"asterisk",
		"mime":content_type,
		"message":base64.b64encode(content).decode('utf-8')
	}
	s = json.dumps(jo)
	buffer = io.BytesIO()
	curl = pycurl.Curl()
	curl.setopt(curl.URL, url)
	curl.setopt(pycurl.VERBOSE, True)
	curl.setopt(pycurl.HTTPHEADER, ["Content-Type: application/json",'Accept: application/json','User-Agent: curl/8.5.0',f"Authorization: Bearer {auth_token}"])
	curl.setopt(curl.POST, 1)
	curl.setopt(curl.POSTFIELDS, s)
	curl.setopt(curl.WRITEDATA, buffer)
	try:
		curl.perform()
		status_code = curl.getinfo(pycurl.RESPONSE_CODE)
		response = buffer.getvalue().decode('utf-8')
		print(f"[Child {os.getpid()}] POST sent to CRM, status: {status_code} {response}")
	except pycurl.error as e:
		print(f"[Child {os.getpid()}] Curl error to CRM: {e}")
	finally:
		curl.close()

def post_to_ai(uid, data, url='http://192.168.10.6:8000/api/core/upload/'):
	status_code = 0
	response = ""
	data = wrap_raw_audio_with_wav(data)
	with open("aii.wav", "wb") as f:
		f.write(data)
	buffer = io.BytesIO()
	curl = pycurl.Curl()
	curl.setopt(curl.URL, url)
	curl.setopt(pycurl.VERBOSE, True)
	curl.setopt(pycurl.HTTPHEADER, [
	'Accept: application/json',
	'User-Agent: curl/8.5.0'
	])
	# curl.setopt(pycurl.USERAGENT, "curl/8.5.0")
	# curl.setopt(curl.POSTFIELDS, bytes(data))
	curl.setopt(pycurl.HTTPPOST, [('audio', (pycurl.FORM_BUFFER,'audio.wav', pycurl.FORM_BUFFERPTR, data, pycurl.FORM_CONTENTTYPE,'audio/wav'))])
	# todo: append uid to form-data
	curl.setopt(curl.WRITEDATA, buffer)
	try:
		curl.perform()
		status_code = curl.getinfo(pycurl.RESPONSE_CODE)
		response = buffer.getvalue().decode('utf-8')
		print(f"[Child {os.getpid()}] POST sent, status: {status_code} {response}")
	except pycurl.error as e:
		print(f"[Child {os.getpid()}] Curl error: {e}")
	finally:
        	curl.close()
	content_type = "text/plain"
	content = "Error: AI service returned an error"
	if status_code == 200:
		o = json.loads(json_string)
		if "response" in o:
			content_type = "application/json"
			content = o["response"].encode().decode('unicode_escape') # unesc
	post_to_crm(uid, content_type, content.encode("utf-8"))

def handle_client(conn, addr):
	print(f"[{os.getpid()}] Handling connection from {addr}")
	buffer = [bytearray(),bytearray()]
	b=0
	try:
		while True:
			data = conn.recv(1024)
			if not data:
				print(f"[{os.getpid()}] Connection closed by {addr}")
				break
			if b == 1:
				buffer[b].extend(data);
				continue
			for index, byte in enumerate(data):
				if byte == 13:
					print(f"Found ASCII 13 at index {index}")
					b=1
					continue
				buffer[b].append(byte)
	except Exception as e:
		print(f"[{os.getpid()}] Error: {e}")
	finally:
		print(f"[Child {os.getpid()}] Final buffer size: {len(buffer)} bytes")
		conn.close()
		post_to_ai(buffer[0], buffer[1])

def start_server(host='127.0.0.1', port=8300):
	server_sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
	server_sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
	server_sock.bind((host, port))
	server_sock.listen()
	print(f"[Main] Listening on {host}:{port}")

	while True:
		conn, addr = server_sock.accept()
		print(f"[Main] Accepted connection from {addr}")
		p = multiprocessing.Process(target=handle_client, args=(conn, addr))
		# p.daemon = True  # Optional: dies with the parent
		p.start()
		#conn.close()  # Important: parent closes its copy

if __name__ == "__main__":
	start_server()
