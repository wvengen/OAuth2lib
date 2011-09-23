<?php
/**
 * Resource example class
 */
include_once('IServerResource.interface.php');
class Resource implements IServerResource {

    /**
     * Function that gets the resource requested by the scope
     * @param <String> $scope
     * @param <Array> $extra Extra parameters
     * @return string Resource
     */
    protected $header;

    public function getResource($scope, $extra=null) {
        //$content = "<Response><ServiceProvider id='uco-consigna' protocol='papiv1' category='0'>	<Name><![CDATA[Consigna de la Universidad de Córdoba]]></Name>	<Description><![CDATA[La Consigna de la Universidad de Córdoba es un servicio para el intercambio de ficheros, donde los usuarios de SIR podrán compartir archivos incluso con usuarios que no pertenezcan a la federación.]]></Description>  <URL><![CDATA[http://consigna.uco.es/]]></URL>  <TechnicalInfo><![CDATA[http://www.rediris.es/sir/sp/consigna-uco.html]]></TechnicalInfo>	<Wayfless><![CDATA[http://sir.rediris.es/idpfirst/?idpid=aesir&spid=uco-consigna]]></Wayfless></ServiceProvider></Response>";
        $content = "Este es un recurso de prueba";
        return $content;
    }

     public function hasHeader() {
        $dev = false;
        if (null!=$this->header) {
            $dev = true;
        }
        return $dev;
    }
    public function getHeader() {
        return $this->header;
    }

}
?>