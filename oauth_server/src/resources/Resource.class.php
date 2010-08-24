<?php
/**
 * Resource example class
 */
require_once('src/resources/IServerResource.interface.php');
class Resource implements IServerResource {

    /**
     * Function that gets the resource requested by the scope
     * TODO
     * @param <String> $scope
     * @param <Array> $extra Extra parameters
     * @return string Resource
     */
    protected $header;

    public function getResource($scope, $extra=null) {
        $content = "<Response><ServiceProvider id='uco-consigna' protocol='papiv1' category='0'>	<Name><![CDATA[Consigna de la Universidad de Córdoba]]></Name>	<Description><![CDATA[La Consigna de la Universidad de Córdoba es un servicio para el intercambio de ficheros, donde los usuarios de SIR podrán compartir archivos incluso con usuarios que no pertenezcan a la federación.]]></Description>  <URL><![CDATA[http://consigna.uco.es/]]></URL>  <TechnicalInfo><![CDATA[http://www.rediris.es/sir/sp/consigna-uco.html]]></TechnicalInfo>	<Wayfless><![CDATA[http://sir.rediris.es/idpfirst/?idpid=aesir&spid=uco-consigna]]></Wayfless></ServiceProvider><ServiceProvider id='foodle' protocol='saml2' category='0'>	<Name><![CDATA[Foodle]]></Name>	<Description><![CDATA[Foodle es un servicio para sondeos o encuestas sencillas y para planificar encuentros.]]></Description>  <URL><![CDATA[https://foodle.feide.no/]]></URL>  <TechnicalInfo><![CDATA[http://www.rediris.es/sir/sp/foodle.html]]></TechnicalInfo>	<Wayfless><![CDATA[http://sir.rediris.es/idpfirst/?idpid=aesir&spid=foodle]]></Wayfless></ServiceProvider><ServiceProvider id='spaces-i2' protocol='saml2' category='0'>	<Name><![CDATA[Wiki federado de Internet2]]></Name>	<Description><![CDATA[El Wiki federado de Internet2 es un espacio de colaboración para actividades y grupos coordinados por la organización Internet2.]]></Description>  <URL><![CDATA[http://spaces.internet2.edu/]]></URL>  <TechnicalInfo><![CDATA[http://www.rediris.es/sir/sp/spaces-i2.html]]></TechnicalInfo>	<Wayfless><![CDATA[http://sir.rediris.es/idpfirst/?idpid=aesir&spid=spaces-i2]]></Wayfless></ServiceProvider><ServiceProvider id='msdn-aa' protocol='msdn-aa' category='1'>	<Name><![CDATA[MSDN Academic Alliance]]></Name>	<Description><![CDATA[Microsoft ofrece en su servicio MSDN Academic Alliance, un portal personalizado para cada universidad española desde el cual los estudiantes y profesores de titulaciones científico-técnicas podrán, a través de la federación SIR, descargarse todos sus productos software con licencia educativa, con la única salvedad de Office y Outlook.]]></Description>  <URL><![CDATA[http://msdn.microsoft.com/es-es/dd350178.aspx]]></URL>  <TechnicalInfo><![CDATA[http://www.rediris.es/sir/sp/msdnaa.html]]></TechnicalInfo>	<Wayfless><![CDATA[http://sir.rediris.es/idpfirst/?idpid=aesir&spid=msdn-aa]]></Wayfless></ServiceProvider><ServiceProvider id='dreamspark' protocol='shib13' category='1'>	<Name><![CDATA[Microsoft DreamSpark]]></Name>	<Description><![CDATA[DreamSpark es un programa a cargo de Microsoft para proveer a los estudiantes con la gran mayoría de sus productos con licencia gratuita educativa.]]></Description>  <URL><![CDATA[https://www.dreamspark.com]]></URL>  <TechnicalInfo><![CDATA[http://www.rediris.es/sir/sp/dreamspark.html]]></TechnicalInfo></ServiceProvider><ServiceProvider id='terena-refeds' protocol='saml2' category='2'>	<Name><![CDATA[REFEDs Wiki]]></Name>	<Description><![CDATA[]]></Description>  <URL><![CDATA[http://refeds.terena.org/]]></URL>  <TechnicalInfo><![CDATA[]]></TechnicalInfo>	<Wayfless><![CDATA[http://sir.rediris.es/idpfirst/?idpid=aesir&spid=terena-refeds]]></Wayfless></ServiceProvider><ServiceProvider id='rediris-irisrbl' protocol='papiv1' category='3'>	<Name><![CDATA[IRISRBL]]></Name>	<Description><![CDATA[Con esta herramienta, las instituciones afiliadas pueden eliminar una dirección IP de la lista que genera el servicio de reputación de RedIRIS: IRISRBL.]]></Description>  <URL><![CDATA[http://www.rediris.es/irisrbl/del/]]></URL>  <TechnicalInfo><![CDATA[http://www.rediris.es/sir/sp/irisrbl.html]]></TechnicalInfo></ServiceProvider><ServiceProvider id='atlases' protocol='shib13' category='4'>	<Name><![CDATA[Atlas of Dermatopathology]]></Name>	<Description><![CDATA[El Atlas de Dermatopatología (1997) contiene más de 3.800 imágenes, tanto clínicas como histológicas. Estas últimas están disponibles en una resolución muy alta gracias al uso de un microscopio virtual que permite acceder a ellas.]]></Description>  <URL><![CDATA[https://atlases.muni.cz/]]></URL>  <TechnicalInfo><![CDATA[http://www.rediris.es/sir/sp/atlases.html]]></TechnicalInfo>	<Wayfless><![CDATA[http://sir.rediris.es/idpfirst/?idpid=aesir&spid=atlases]]></Wayfless></ServiceProvider></Response>";
        return $content;
    }

    /**
     * Function that checks if the scope is available for the person_id
     * TODO
     * @param <type> $scope
     * @param <type> $person_id
     * @return <type>
     */
    public function checkScope($scope, $person_id=null) {
        return true;
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