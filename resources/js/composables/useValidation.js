import { ref, watch } from 'vue';

/**
 * Reusable composable for form field validation
 * 
 * @param {Function} validationFunction - Function that returns true if validation passes
 * @param {Object} options - Configuration options
 * @returns {Object} - Validation utilities
 */
export function useValidation(validationFunction, options = {}) {
  const { 
    defaultMessage = 'Dieses Feld ist erforderlich.',
    emitEvents = true,
    emit = null
  } = options;
  
  // State
  const showValidationErrors = ref(false);
  const isValid = ref(false);
  
  // Update validation status
  const updateValidation = () => {
    isValid.value = validationFunction();
    
    // Emit validation events if needed
    if (emitEvents && emit) {
      emit('validation', isValid.value);
    }
    
    return isValid.value;
  };
  
  // Method to trigger validation
  const validate = () => {
    updateValidation();
    showValidationErrors.value = !isValid.value;
    return isValid.value;
  };
  
  // Method to show validation errors
  const showErrors = () => {
    showValidationErrors.value = true;
  };
  
  // Method to hide validation errors
  const hideErrors = () => {
    showValidationErrors.value = false;
  };
  
  // Watch for validation changes (optional)
  if (options.watchCallback) {
    watch(options.watchCallback, () => {
      updateValidation();
      
      // Hide errors when field becomes valid
      if (isValid.value && showValidationErrors.value) {
        showValidationErrors.value = false;
      }
    });
  }
  
  // Initial validation
  updateValidation();
  
  // Return all needed values and methods
  return {
    isValid,
    showValidationErrors,
    validate,
    showErrors,
    hideErrors,
    updateValidation
  };
}