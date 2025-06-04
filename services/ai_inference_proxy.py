import socket
import multiprocessing
import os
import pycurl
import io
import wave

def wrap_raw_audio_with_wav(data, sample_rate=16000, channels=1, sampwidth=2):
	# Wraps raw 16-bit signed PCM data with a WAV header
	wav_io = io.BytesIO()
	wav_file = wave.open(wav_io, 'wb')
	wav_file.setnchannels(channels)
	wav_file.setsampwidth(sampwidth)  # 2 bytes = 16 bits
	wav_file.setframerate(sample_rate)
	wav_file.writeframes(data)
	return wav_io.getvalue()

def send_post_request(data, url='http://192.168.10.6:8000/api/core/upload/'):
	with open("aii.wav", "wb") as f:
		f.write(data)
	buffer = io.BytesIO()
	curl = pycurl.Curl()
	curl.setopt(curl.URL, url)
	curl.setopt(pycurl.HTTPHEADER, [
	'Accept: application/json',
	'User-Agent: curl/8.5.0'
	])
	# curl.setopt(pycurl.USERAGENT, "curl/8.5.0")
	# curl.setopt(curl.POSTFIELDS, bytes(data))
	curl.setopt(pycurl.HTTPPOST, [('audio', (pycurl.FORM_BUFFER,'audio.wav', pycurl.FORM_BUFFERPTR, data, pycurl.FORM_CONTENTTYPE,'audio/wav'))])
	curl.setopt(curl.WRITEDATA, buffer)
	curl.setopt(pycurl.VERBOSE, True)
	try:
		curl.perform()
		status_code = curl.getinfo(pycurl.RESPONSE_CODE)
		response = buffer.getvalue().decode('utf-8')
		print(f"[Child {os.getpid()}] POST sent, status: {status_code} {response}")
	except pycurl.error as e:
		print(f"[Child {os.getpid()}] Curl error: {e}")
	finally:
        	curl.close()

def handle_client(conn, addr):
	print(f"[{os.getpid()}] Handling connection from {addr}")
	buffer = bytearray()
	try:
		while True:
			data = conn.recv(1024)
			if not data:
				print(f"[{os.getpid()}] Connection closed by {addr}")
				break
			buffer.extend(data)
			# print(f"[{os.getpid()}] Received")
			# conn.sendall(data)  # Echo back
	except Exception as e:
		print(f"[{os.getpid()}] Error: {e}")
	finally:
		print(f"[Child {os.getpid()}] Final buffer size: {len(buffer)} bytes")
		conn.close()
		send_post_request(wrap_raw_audio_with_wav(buffer))

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
