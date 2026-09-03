import cyclistsAndChildrenTopicImage from '../../images/session/cyclists-and-children-topic.webp';
import drivingWithTrailerTopicImage from '../../images/session/driving-with-trailer-topic.webp';
import informationalSignsTopicImage from '../../images/session/informational-signs-topic.webp';
import ownerObligationsInsuranceDocumentsTopicImage from '../../images/session/owner-obligations-insurance-documents-topic.webp';
import perceptionFatigueAlcoholTopicImage from '../../images/session/perception-fatigue-alcohol-topic.webp';
import prohibitionMandatorySignsTopicImage from '../../images/session/prohibition-mandatory-signs-topic.webp';
import railAndTramCrossingsTopicImage from '../../images/session/rail-and-tram-crossings-topic.webp';
import roadMarkingsTopicImage from '../../images/session/road-markings-topic.webp';
import rescueActionsTopicImage from '../../images/session/rescue-actions-topic.webp';
import trafficLightsControllerTopicImage from '../../images/session/traffic-lights-controller-topic.webp';
import vehicleLightsSignalsTopicImage from '../../images/session/vehicle-lights-signals-topic.webp';
import warningSignsTopicImage from '../../images/session/warning-signs-topic.webp';

const topicArtworkByKey: Record<string, string> = {
    warning_signs: warningSignsTopicImage,
    prohibition_and_mandatory_signs: prohibitionMandatorySignsTopicImage,
    behaviour_towards_cyclists_and_children: cyclistsAndChildrenTopicImage,
    driving_with_trailer: drivingWithTrailerTopicImage,
    informational_direction_and_supplementary_signs: informationalSignsTopicImage,
    owner_obligations_insurance_documents: ownerObligationsInsuranceDocumentsTopicImage,
    perception_decision_alcohol_fatigue: perceptionFatigueAlcoholTopicImage,
    rail_and_tram_crossings: railAndTramCrossingsTopicImage,
    road_markings: roadMarkingsTopicImage,
    rescue_actions: rescueActionsTopicImage,
    traffic_lights_and_controller_signals: trafficLightsControllerTopicImage,
    vehicle_lights_and_signals: vehicleLightsSignalsTopicImage,
};

export function topicArtworkForKey(topicKey: string): string | null {
    return topicArtworkByKey[topicKey] ?? null;
}
