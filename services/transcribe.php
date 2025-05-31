<?php
/*
Transcribe audio after end of a call recording and post the result to CRM
*/

// curl 'http://192.168.10.6:11434/api/generate' -H 'Content-Type: application/json' -D $'{"model":"mistral","prompt":"You are a trauma-informed social worker conducting an expert case analysis. Analyze the following case details and return a comprehensive JSON response with the following structure:\\n\\n{\\n  \\"case_summary\\": \\"Brief 2-3 sentence overview of the case\\",\\n  \\"named_entities\\": {\\n    \\"persons\\": [],\\n    \\"organizations\\": [],\\n    \\"locations\\": [],\\n    \\"dates\\": [],\\n    \\"contact_information\\": []\\n  },\\n  \\"classification\\": {\\n    \\"category\\": [\\"Select applicable categories\\"],\\n    \\"interventions_needed\\": [\\"List required interventions\\"],\\n    \\"priority_level\\": \\"high/medium/low\\" \\n  },\\n  \\"case_management\\": {\\n    \\"safety_planning\\": {\\n      \\"immediate_actions\\": [],\\n      \\"long_term_measures\\": []\\n    },\\n    \\"psychosocial_support\\": {\\n      \\"short_term\\": [],\\n      \\"long_term\\": []\\n    },\\n    \\"legal_protocols\\": {\\n      \\"applicable_laws\\": [],\\n      \\"required_documents\\": [],\\n      \\"authorities_to_contact\\": []\\n    },\\n    \\"medical_protocols\\": {\\n      \\"immediate_needs\\": [],\\n      \\"follow_up_care\\": []\\n    }\\n  },\\n  \\"risk_assessment\\": {\\n    \\"red_flags\\": [],\\n    \\"potential_barriers\\": [],\\n    \\"protective_factors\\": []\\n  },\\n  \\"cultural_considerations\\": []\\n}\\n\\nAvailable categories for classification:\\n- Labor exploitation\\n- Wage theft\\n- Workplace abuse\\n- Human trafficking\\n- Psychological distress\\n- Housing insecurity\\n- Legal aid needed\\n- Medical attention needed\\n\\nInstructions:\\n1. Extract ALL named entities (people, organizations, locations, dates, contact info)\\n2. Classify the case by category, required interventions, and priority level\\n3. Provide detailed safety planning measures\\n4. Specify psychosocial support needs with timeframes\\n5. List all applicable legal protocols and required documents\\n6. Outline medical protocols based on survivor needs\\n7. Conduct thorough risk assessment including protective factors\\n8. Highlight cultural considerations for service delivery\\n\\nCase Details:\\nThe caller reported that his friend of about 19 years is being mistreated by the father. Her father insists that the girl is 17 years old. She is denied all her human rights, she is not allowed to move and meet her friends. As a result, the girl has tried to poison herself and commit suicide several times. Recently, she drove a car out of the gate with a lot of anger, she knocked the wall and obtained injuries and she is in the hospital receiving treatment. The caller mentioned that the girl once called a team from the human rights commission, who visited the home but they told her that they could not help her since they were bribed by the father. They connected her to Mr. Mugisha who went to the home but the girl said that she was not in her right mind and the gentleman promised to get back to them after a week. The caller was requesting the personal contact of Mr.Mugisha for him to follow up on the issue with him but we could not share it and requested him to be patient. He said that the girl father is found of blocking communications meant to the girl.  \\n\\nResponse Requirements:\\n- Be specific, culturally sensitive, and trauma-informed\\n- Focus on survivor autonomy and empowerment\\n- Reference Tanzanian context where applicable\\n- Provide actionable recommendations\\n- Use clear, concise language\\n- Return ONLY valid JSON (no commentary)","stream":false}'
// curl -X POST http://192.168.10.6:11434/api/generate -H "Content-Type: application/json" --data @mistral.json

define('FIFOPATH','/var/spool/asterisk/monitor/123456789.wav');
define('APIURL','');
define('FFMPEGCMD','ffmpeg -f s16le -ar 8000 -ac 1 -i pipe:0 -ar 16000 -ac 1 -f wav pipe:1');

function io ()
{ 
	$fifo = fopen(FIFOPATH, 'r');
	if (!$fifo) 
	{
		error_log ("Failed to open namedpipe for reading.\n");
		return -1;
	}
	$data = stream_get_contents($fifo);
	if ($data === false || trim($data) === '') 	// Avoid empty reads 
	{
		error_log ("empty reading.\n");
		return -1;
	}
	$descriptors = 
	[
		0 => ['pipe', 'r'],  // stdin
    		1 => ['pipe', 'w'],  // stdout
		2 => ['pipe', 'w'],  // stderr
	];
	$process = proc_open ($FFMPEGCMD, $descriptors, $pipes);
	if (!is_resource($process)) 
	{
    		error_log("Failed to start FFmpeg process.\n");
		return -1;
	}
	fwrite($pipes[0], $data);
	fclose($pipes[0]); 				// Important: close stdin so FFmpeg knows input has ended
	$outputWav = stream_get_contents($pipes[1]); 	// Read the output WAV data from FFmpeg's stdout
	fclose($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);	// Optionally read any error output (for debugging)
	fclose($pipes[2]);
	$returnCode = proc_close($process);		// Close FFmpeg process
	if ($returnCode !== 0) 
	{
    		error_log("FFmpeg error:".$stderr);
    		return -1;
	}
	$r = array ('data'=>'', 'info'=>0);
	$ch = curl_init ();
        curl_setopt ($ch, CURLOPT_URL, APIURL);
        curl_setopt ($ch, CURLOPT_HEADER, false);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
    	curl_setopt ($ch, CURLOPT_HTTPHEADER, $hdrs);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	$r['data'] = curl_exec ($ch);
        $r['info'] = curl_getinfo ($ch);
        error_log ("[curl_info] ". $r['info']['http_code'] ."|".json_encode ($r['info']));
        error_log ("[curl_result] ".$r['info']['http_code']." | ". $r['data']);

	// todo: POST response to CRM
}

while (1) 
io();

?>