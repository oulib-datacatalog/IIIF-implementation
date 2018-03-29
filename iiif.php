<?php
	define("ROOT_PATH", "/usr/share/nginx/html/images/");
    $request_uri = explode('?', $_SERVER['REQUEST_URI'], 2);

    $uri = strtolower($request_uri[0]);

    if($uri === '/'){
    	header('Location: index.php'); 
    	exit();
    }

    if(preg_match('/^\/iiif\/([a-z0-9]+)\/[a-f0-9]{8}\-[a-f0-9]{4}\-4[a-f0-9]{3}\-(8|9|a|b)[a-f0-9]{3‌​}\-[a-f0-9]{12}\//'), $uri, $matches){
    	print_r($matches);
    	exit();
    }
 
    if(preg_match('/^\/iiif\/collections\/([a-z0-9]+)\/(thumbnail|manifest|(sequences\/([a-z0-9]+))|(canvases\/([a-z0-9]+))|(images\/([a-z0-9]+)\/info))$/', $uri, $matches)){
    	$req_type = $matches[0];
		$collection_id = $matches[1];
		$collection_manifest_data = getCollectionManifestData($collection_id);

		if(preg_match('/\/(thumbnail|manifest)$/', $req_type, $sub_matches)){

			switch ($sub_matches[1]) {
				case 'manifest':
					header('Content-type: application/json');
					echo $collection_manifest_data;
					break;
				case 'thumbnail':
					$thumbnail_path = ROOT_PATH."$collection_id/thumbnail.jpg";
					if (file_exists($thumbnail_path)) {
					    header('Content-Description: File Transfer');
					    header('Content-Type: application/octet-stream');
					    header('Content-Disposition: attachment; filename="'.basename($thumbnail_path).'"');
					    header('Expires: 0');
					    header('Cache-Control: must-revalidate');
					    header('Pragma: public');
					    header('Content-Length: ' . filesize($thumbnail_path));
					    readfile($thumbnail_path);		    
					}
					break;
				default:
					echo "No match services are provided with request type: ".$req_type."\n";
					break;
			}
		}
		elseif(preg_match('/\/((sequences\/([a-z0-9]+))|(canvases\/([a-z0-9]+)))$/', $req_type, $sub_matches)){

			$requested_info = explode("/", $sub_matches[1]);
			$request_type = $requested_info[0];
			$request_id = $requested_info[1];
			$collection_manifest_data_obj = json_decode($collection_manifest_data);

			header('Content-type: application/json');

			switch ($request_type) {

				case 'sequences':
					$requested_data_array = $collection_manifest_data_obj->$request_type; 
					foreach ($requested_data_array as $key => $value) {				
						$id_value = $value->{'@id'};
						if(preg_match('/\/(sequences\/'.$request_id.')$/', $id_value, $id_matches)){
							echo json_encode($value);
						}
					}
					break;

				case 'canvases':
					$sequence_data_array = $collection_manifest_data_obj->sequences;
					foreach ($sequence_data_array as $sequence_key => $sequence_value) {
						$canvases = $sequence_value->canvases;
						foreach ($canvases as $canvas_key => $canvas_value) {							
							$id_value = $canvas_value->{'@id'};
							if(preg_match('/\/(canvases\/'.$request_id.')$/', $id_value, $id_matches)){
								echo json_encode($canvas_value);
							}
						}
					}
					break;

				default:
					throw new Exception('Cannot process request: '.$request_uri);
					break;
			}
			
		}
		elseif(preg_match('/\/(images\/([a-z0-9]+)\/info)$/', $req_type, $sub_matches)){
			header('Content-type: application/json');
			
			$sequence_data_array = $collection_manifest_data_obj->sequences;
			foreach ($sequence_data_array as $sequence_key => $sequence_value) {
				$canvases = $sequence_value->canvases;
				foreach ($canvases as $canvas_key => $canvas_value) {
					$image_data = $canvas_value->images;
					foreach ($image_data as $image_key => $image_value) {
						$id_value = $image_value->{'@id'};
						if(preg_match('/\/(images\/'.$request_id.')$/', $id_value, $id_matches)){
							echo json_encode($image_value);
						}
					}							
					
				}
			}
		}

		// $thumbnail_path = ROOT_PATH."$collection_id/thumbnail.jpg";

		// if (file_exists($thumbnail_path)) {
		//     header('Content-Description: File Transfer');
		//     header('Content-Type: application/octet-stream');
		//     header('Content-Disposition: attachment; filename="'.basename($thumbnail_path).'"');
		//     header('Expires: 0');
		//     header('Cache-Control: must-revalidate');
		//     header('Pragma: public');
		//     header('Content-Length: ' . filesize($thumbnail_path));
		//     readfile($thumbnail_path);		    
		// }
		exit();
	}
    else {
    	echo "No matches!";
    }

    function getCollectionManifestData($collection_id){
    	$manifest_path = ROOT_PATH."$collection_id/manifest.json";
    	$data = file_get_contents($manifest_path);
    	return $data;
    }

    function getImageInfoPath($collection_id, $image_id){
    	$image_info_path = ROOT_PATH."$collection_id/$image_id/info.json";
    	return $image_info_path;
    }